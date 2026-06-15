document.addEventListener("DOMContentLoaded", () => {
  // 1. DOM 与运行环境
  const form = document.getElementById("productFilterForm");

  if (!form) {
    return;
  }

  const config = window.pflProductFilter || {};
  const ajaxEnabled = Boolean(
    config.ajaxUrl
    && config.nonce
    && window.fetch
    && window.URL
    && window.URLSearchParams
    && window.history
  );

  const panel = document.getElementById("product-filter-panel");
  const countElement = document.getElementById("productFilterResultCount");
  const mobileCountElement = document.getElementById("mobileFilterResultCount");
  const stickyCountElement = document.getElementById("stickyFilterResultCount");
  const mobileAppliedCount = document.getElementById("mobileFilterAppliedCount");
  const stickyAppliedCount = document.getElementById("stickyFilterAppliedCount");
  const liveRegion = document.getElementById("productFilterLiveRegion");
  const selectedTags = document.getElementById("selectedFilterTags");
  const stateTitle = document.getElementById("filterStateTitle");
  const stateHint = document.getElementById("filterStateHint");
  const performanceElement = document.getElementById("filterPerformance");
  const clearAllButton = document.getElementById("clearAllFilters");
  const mobileClearButton = document.getElementById("mobileClearAllFilters");
  const stickyClearButton = document.getElementById("stickyClearFilters");
  const applyButton = document.getElementById("applyFilterButton");
  const mobileViewButton = document.getElementById("mobileViewResults");
  const sortSelect = form.querySelector("select[data-filter-sort]");

  const drawerOpenButton = document.getElementById("productFilterDrawerOpen");
  const drawerCloseButton = document.getElementById("productFilterDrawerClose");
  const drawerBackdrop = document.getElementById("productFilterDrawerBackdrop");
  const stickyModifyButton = document.getElementById("stickyModifyFilters");

  const ajaxError = document.getElementById("filterAjaxError");
  const ajaxErrorMessage = document.getElementById("filterAjaxErrorMessage");
  const ajaxRetryButton = document.getElementById("filterAjaxRetry");
  const ajaxFallbackLink = document.getElementById("filterAjaxFallback");

  const filterInputs = Array.from(
    form.querySelectorAll(
      'input[type="checkbox"][data-filter-label], input[type="radio"][data-filter-label]'
    )
  );
  const filterGroups = Array.from(form.querySelectorAll("[data-filter-group]"));
  const groupClearButtons = Array.from(form.querySelectorAll("[data-clear-filter-group]"));

  const breakpoint = Number(config.uiBreakpoint) || 820;
  const mobileMedia = window.matchMedia(`(max-width: ${breakpoint}px)`);
  const storageKey = `${config.storagePrefix || "pfl_filter_ui_"}${form.dataset.storageKey || "archive_0"}`;

  // 2. 前端界面状态：真正的筛选值始终由 URL 管理
  const uiState = {
    loading: false,
    drawerOpen: false,
    expandedGroups: new Set(),
    expandedOptions: new Set(),
    resultCount: Number.parseInt(countElement?.textContent || "0", 10) || 0,
    hasSavedState: false
  };

  let appliedState = serializeState();
  let requestController = null;
  let requestNumber = 0;
  let debounceTimer = null;
  let lastFocusedElement = null;
  let lastRequestOptions = null;

  document.documentElement.classList.add("pfl-filter-ui-ready");

  if (ajaxEnabled) {
    document.documentElement.classList.add("pfl-ajax-enabled");

    if (!history.state || !history.state.pflProductFilter) {
      history.replaceState({ pflProductFilter: true }, "", window.location.href);
    }
  }

  function safelyReadUiState() {
    try {
      const rawState = window.localStorage.getItem(storageKey);

      if (!rawState) {
        return;
      }

      const saved = JSON.parse(rawState);
      uiState.hasSavedState = true;

      if (Array.isArray(saved.expandedGroups)) {
        saved.expandedGroups.forEach((key) => uiState.expandedGroups.add(String(key)));
      }

      if (Array.isArray(saved.expandedOptions)) {
        saved.expandedOptions.forEach((key) => uiState.expandedOptions.add(String(key)));
      }
    } catch (error) {
      // localStorage 不可用时退回当前页面默认状态。
    }
  }

  function saveUiState() {
    try {
      window.localStorage.setItem(
        storageKey,
        JSON.stringify({
          expandedGroups: Array.from(uiState.expandedGroups),
          expandedOptions: Array.from(uiState.expandedOptions)
        })
      );
    } catch (error) {
      // 隐私模式或存储空间受限时不影响筛选功能。
    }
  }

  // 3. 表单与 URL 状态
  function getFormParams() {
    const params = new URLSearchParams();
    const formData = new FormData(form);

    formData.forEach((value, key) => {
      const stringValue = String(value);

      if (stringValue !== "") {
        params.append(key, stringValue);
      }
    });

    params.delete("paged");
    return params;
  }

  function serializeState() {
    return Array.from(getFormParams().entries())
      .map(([key, value]) => `${key}=${value}`)
      .sort()
      .join("&");
  }

  function getSelectedFilterCount() {
    return filterInputs.filter((input) => input.checked).length;
  }

  function getCurrentPageFromUrl(urlValue = window.location.href) {
    const url = new URL(urlValue, window.location.href);
    const queryPage = Number.parseInt(url.searchParams.get("paged") || "", 10);

    if (Number.isInteger(queryPage) && queryPage > 0) {
      return queryPage;
    }

    const pathMatch = url.pathname.match(/\/page\/(\d+)\/?$/);
    return pathMatch ? Math.max(1, Number.parseInt(pathMatch[1], 10)) : 1;
  }

  function buildClientStateUrl(page = 1) {
    const baseUrl = form.dataset.baseUrl || form.action;
    const url = new URL(baseUrl, window.location.href);
    const params = getFormParams();

    url.search = "";
    url.hash = "";

    params.forEach((value, key) => url.searchParams.append(key, value));

    if (page > 1) {
      url.searchParams.set("paged", String(page));
    }

    return url.toString();
  }

  function getValuesForInputFromUrl(input, url) {
    const exactName = input.name;
    const baseName = exactName.endsWith("[]") ? exactName.slice(0, -2) : exactName;
    const values = [];

    url.searchParams.forEach((value, key) => {
      if (key === exactName || key === baseName || key.startsWith(`${baseName}[`)) {
        values.push(value);
      }
    });

    return values;
  }

  function syncFormFromUrl(urlValue) {
    const url = new URL(urlValue, window.location.href);

    filterInputs.forEach((input) => {
      const values = getValuesForInputFromUrl(input, url);
      input.checked = values.includes(input.value);

      if (input.checked) {
        input.disabled = false;
      }
    });

    if (sortSelect) {
      sortSelect.value = url.searchParams.get("sort") || "";
    }

    refreshUI();
  }

  // 4. 筛选组折叠、搜索和“更多”
  function setGroupExpanded(group, expanded, persist = true) {
    const key = group.dataset.filterGroup;
    const toggle = group.querySelector(".filter-group-toggle");
    const body = group.querySelector(".filter-row__body");

    group.classList.toggle("is-open", expanded);
    toggle?.setAttribute("aria-expanded", expanded ? "true" : "false");

    if (body) {
      body.hidden = !expanded;
    }

    if (expanded) {
      uiState.expandedGroups.add(key);
    } else {
      uiState.expandedGroups.delete(key);
    }

    if (persist) {
      saveUiState();
    }
  }

  function getGroupOptionLabels(group) {
    return Array.from(group.querySelectorAll("[data-filter-option]"));
  }

  function getOptionCount(label) {
    return Number.parseInt(label.querySelector("[data-filter-count]")?.textContent || "0", 10) || 0;
  }

  function orderAndLimitGroupOptions(group) {
    const key = group.dataset.filterGroup;
    const labels = getGroupOptionLabels(group);
    const limit = Math.max(1, Number.parseInt(group.dataset.initialLimit || "99", 10));
    const expanded = uiState.expandedOptions.has(key);
    const searchInput = group.querySelector("[data-filter-option-search]");
    const searchValue = (searchInput?.value || "").trim().toLocaleLowerCase();
    const moreButton = group.querySelector("[data-filter-options-more]");
    const emptyMessage = group.querySelector("[data-filter-search-empty]");

    const ordered = labels.slice().sort((a, b) => {
      const inputA = a.querySelector("input");
      const inputB = b.querySelector("input");
      const selectedA = inputA?.checked ? 1 : 0;
      const selectedB = inputB?.checked ? 1 : 0;

      if (selectedA !== selectedB) {
        return selectedB - selectedA;
      }

      const disabledA = inputA?.disabled ? 1 : 0;
      const disabledB = inputB?.disabled ? 1 : 0;

      if (disabledA !== disabledB) {
        return disabledA - disabledB;
      }

      const countDifference = getOptionCount(b) - getOptionCount(a);

      if (countDifference !== 0) {
        return countDifference;
      }

      return Number(a.dataset.originalIndex || 0) - Number(b.dataset.originalIndex || 0);
    });

    ordered.forEach((label, index) => {
      label.style.order = String(index);
    });

    const matchingLabels = ordered.filter((label) => {
      const name = (label.dataset.optionName || "").toLocaleLowerCase();
      return !searchValue || name.includes(searchValue);
    });

    let visibleRegularCount = 0;

    ordered.forEach((label) => {
      const input = label.querySelector("input");
      const selected = Boolean(input?.checked);
      const name = (label.dataset.optionName || "").toLocaleLowerCase();
      const matchesSearch = !searchValue || name.includes(searchValue);
      let visible = matchesSearch;

      if (visible && !searchValue && !expanded && !selected) {
        visible = visibleRegularCount < limit;
        visibleRegularCount += 1;
      }

      label.hidden = !visible;
    });

    if (emptyMessage) {
      emptyMessage.hidden = matchingLabels.length > 0;
    }

    if (moreButton) {
      const selectedCount = ordered.filter((label) => label.querySelector("input")?.checked).length;
      const hasOverflow = ordered.length - selectedCount > limit;
      moreButton.hidden = Boolean(searchValue) || !hasOverflow;
      moreButton.textContent = expanded
        ? "收起部分选项"
        : `显示更多 ${Math.max(0, ordered.length - selectedCount - limit)} 项`;
      moreButton.setAttribute("aria-expanded", expanded ? "true" : "false");
    }
  }

  function refreshOptionClasses() {
    form.querySelectorAll(".filter-option").forEach((label) => {
      const input = label.querySelector("input");
      const selected = Boolean(input?.checked);
      const disabled = Boolean(input?.disabled && !selected);

      label.classList.toggle("is-selected", selected);
      label.classList.toggle("is-disabled", disabled);

      if (disabled) {
        label.setAttribute("aria-disabled", "true");
      } else {
        label.removeAttribute("aria-disabled");
      }
    });
  }

  function refreshGroupState() {
    filterGroups.forEach((group) => {
      const selected = Array.from(
        group.querySelectorAll('input[type="checkbox"]:checked, input[type="radio"]:checked')
      );
      const countLabel = group.querySelector("[data-group-selected-count]");
      const clearButton = group.querySelector("[data-clear-filter-group]");

      if (countLabel) {
        countLabel.textContent = selected.length > 0 ? `已选 ${selected.length} 项` : "未选择";
      }

      if (clearButton) {
        clearButton.hidden = selected.length === 0;
      }

      if (selected.length > 0 && !group.classList.contains("is-open")) {
        setGroupExpanded(group, true, false);
      }

      orderAndLimitGroupOptions(group);
    });
  }

  function renderSelectedTags() {
    if (!selectedTags) {
      return;
    }

    selectedTags.innerHTML = "";

    filterInputs.filter((input) => input.checked).forEach((input) => {
      const tag = document.createElement("span");
      tag.className = "selected-filter-tag";

      const text = document.createElement("span");
      text.textContent = `${input.dataset.filterLabel}：${input.dataset.optionLabel}`;

      const removeButton = document.createElement("button");
      removeButton.type = "button";
      removeButton.textContent = "×";
      removeButton.setAttribute(
        "aria-label",
        `移除 ${input.dataset.filterLabel} ${input.dataset.optionLabel}`
      );
      removeButton.addEventListener("click", () => {
        input.checked = false;
        refreshUI();
        scheduleAjaxUpdate();
      });

      tag.append(text, removeButton);
      selectedTags.appendChild(tag);
    });
  }

  function updateSummaryCounts() {
    const selectedCount = getSelectedFilterCount();

    [mobileAppliedCount, stickyAppliedCount].forEach((element) => {
      if (element) {
        element.textContent = String(selectedCount);
      }
    });

    [countElement, mobileCountElement, stickyCountElement].forEach((element) => {
      if (element) {
        element.textContent = String(uiState.resultCount);
      }
    });

    if (mobileViewButton) {
      mobileViewButton.disabled = uiState.loading;
      mobileViewButton.setAttribute(
        "aria-label",
        uiState.loading
          ? "正在计算产品结果"
          : `关闭筛选并查看 ${uiState.resultCount} 个产品`
      );
    }
  }

  function renderStateMessage() {
    const selectedCount = getSelectedFilterCount();
    const pending = serializeState() !== appliedState;

    panel?.classList.toggle("has-pending-changes", pending && !uiState.loading);
    panel?.classList.toggle("is-loading", uiState.loading);

    if (stateTitle) {
      if (uiState.loading) {
        stateTitle.textContent = "正在更新产品结果";
      } else if (pending) {
        stateTitle.textContent = selectedCount > 0
          ? `已选择 ${selectedCount} 个条件，正在等待自动更新`
          : "筛选条件已清空，正在等待自动更新";
      } else {
        stateTitle.textContent = selectedCount > 0
          ? `当前已应用 ${selectedCount} 个筛选条件`
          : "当前未应用筛选条件";
      }
    }

    if (stateHint) {
      if (uiState.loading) {
        stateHint.textContent = "正在更新产品、分页和筛选项数量。";
      } else if (pending && ajaxEnabled) {
        stateHint.textContent = "筛选或排序发生变化，系统将自动更新产品列表。";
      } else if (ajaxEnabled) {
        stateHint.textContent = "结果已同步到地址栏；折叠和更多展开状态保存在当前浏览器。";
      } else {
        stateHint.textContent = pending
          ? "点击“查看筛选结果”后生效。"
          : "页面中的产品已经按照当前 URL 参数查询。";
      }
    }

    if (applyButton) {
      applyButton.disabled = uiState.loading;
      applyButton.textContent = uiState.loading
        ? "正在更新……"
        : (pending ? "立即更新结果" : "查看筛选结果");
    }
  }

  function refreshUI() {
    refreshOptionClasses();
    refreshGroupState();
    renderSelectedTags();
    updateSummaryCounts();
    renderStateMessage();
  }

  function announce(message) {
    if (!liveRegion) {
      return;
    }

    liveRegion.textContent = "";
    window.setTimeout(() => { liveRegion.textContent = message; }, 30);
  }

  function setLoading(loading) {
    uiState.loading = loading;
    const results = document.getElementById("product-results");

    if (results) {
      results.classList.toggle("is-loading", loading);
      results.setAttribute("aria-busy", loading ? "true" : "false");
    }

    panel?.classList.toggle("is-counting-facets", loading);
    updateSummaryCounts();
    renderStateMessage();
  }

  function replaceResults(html) {
    const currentResults = document.getElementById("product-results");
    const template = document.createElement("template");
    template.innerHTML = String(html).trim();
    const newResults = template.content.firstElementChild;

    if (!currentResults || !newResults) {
      throw new Error("服务器没有返回有效的产品结果区域。");
    }

    currentResults.replaceWith(newResults);
  }

  function updateResultCount(count) {
    uiState.resultCount = Number.parseInt(count, 10) || 0;
    updateSummaryCounts();
  }

  // 5. AJAX 返回的结果与联动计数
  function updateFacetState(facets) {
    if (!facets || typeof facets !== "object") {
      return;
    }

    filterInputs.forEach((input) => {
      const key = input.dataset.filterKey;
      const value = input.dataset.optionValue;
      const optionState = facets?.[key]?.options?.[value];

      if (!optionState) {
        return;
      }

      const count = Number.parseInt(optionState.count, 10);
      const selected = input.checked;
      const label = input.closest(".filter-option");
      const countForOption = label?.querySelector("[data-filter-count]");

      input.disabled = Boolean(optionState.disabled) && !selected;

      if (countForOption) {
        countForOption.textContent = Number.isFinite(count) ? String(count) : "0";
      }
    });

    refreshOptionClasses();
    refreshGroupState();
  }

  function updatePerformance(meta) {
    if (!performanceElement || !config.showPerformance || !meta) {
      return;
    }

    const elapsed = Number(meta.elapsedMs || 0).toFixed(2);
    performanceElement.hidden = false;
    performanceElement.textContent = `${meta.cacheHit ? "缓存命中" : "实时计算"} · ${elapsed} ms`;
  }

  function hideAjaxError() {
    if (ajaxError) {
      ajaxError.hidden = true;
    }
  }

  function showAjaxError(message, targetUrl) {
    if (!ajaxError) {
      window.location.assign(targetUrl);
      return;
    }

    ajaxError.hidden = false;

    if (ajaxErrorMessage) {
      ajaxErrorMessage.textContent = message;
    }

    if (ajaxFallbackLink) {
      ajaxFallbackLink.href = targetUrl;
    }

    ajaxError.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }


  // 7. AJAX 请求：失败后由用户选择“重新请求”或普通 GET 页面
  async function requestProducts({
    page = 1,
    historyMode = "push",
    focusResults = false
  } = {}) {
    if (!ajaxEnabled) {
      window.location.assign(buildClientStateUrl(page));
      return;
    }

    lastRequestOptions = { page, historyMode, focusResults };
    window.clearTimeout(debounceTimer);

    if (requestController) {
      requestController.abort();
    }

    requestController = new AbortController();
    const currentRequest = ++requestNumber;
    const targetUrl = buildClientStateUrl(page);
    const body = new URLSearchParams();

    body.set("action", config.action);
    body.set("nonce", config.nonce);
    body.set("query", getFormParams().toString());
    body.set("context", form.dataset.ajaxContext || "archive");
    body.set("term_id", form.dataset.termId || "0");
    body.set("paged", String(Math.max(1, page)));

    hideAjaxError();
    setLoading(true);
    announce(config.messages?.loading || "正在更新产品结果。");

    try {
      const response = await fetch(config.ajaxUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: body.toString(),
        signal: requestController.signal
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const payload = await response.json();

      if (!payload.success || !payload.data || !payload.data.html) {
        throw new Error("AJAX 返回格式无效。");
      }

      if (currentRequest !== requestNumber) {
        return;
      }

      replaceResults(payload.data.html);
      updateResultCount(payload.data.foundPosts);
      updateFacetState(payload.data.facets);
      updatePerformance(payload.data.facetMeta);
      appliedState = serializeState();

      if (historyMode === "push") {
        history.pushState({ pflProductFilter: true }, "", payload.data.url || targetUrl);
      } else if (historyMode === "replace") {
        history.replaceState({ pflProductFilter: true }, "", payload.data.url || targetUrl);
      }

      setLoading(false);
      refreshUI();
      announce(payload.data.announcement || `筛选完成，共找到 ${uiState.resultCount} 个产品。`);

      if (focusResults) {
        scrollToResults(true);
      }
    } catch (error) {
      if (error.name === "AbortError") {
        return;
      }

      setLoading(false);

      const message = config.messages?.error || "AJAX 请求仍未成功，可重新请求或使用普通页面打开。";
      announce(message);
      showAjaxError(message, targetUrl);
    }
  }

  function scheduleAjaxUpdate() {
    if (!ajaxEnabled) {
      return;
    }

    window.clearTimeout(debounceTimer);

    if (requestController) {
      requestController.abort();
      requestController = null;
      requestNumber += 1;
      setLoading(false);
    }

    debounceTimer = window.setTimeout(() => {
      requestProducts({ page: 1, historyMode: "push", focusResults: false });
    }, Number(config.debounce) || 320);
  }

  function clearAllControls() {
    filterInputs.forEach((input) => { input.checked = false; });

    if (sortSelect) {
      sortSelect.value = "";
    }

    filterGroups.forEach((group) => {
      const searchInput = group.querySelector("[data-filter-option-search]");
      if (searchInput) {
        searchInput.value = "";
      }
    });

    refreshUI();
  }

  function scrollToResults(focus = false) {
    const results = document.getElementById("product-results");

    if (!results) {
      return;
    }

    if (focus) {
      results.focus({ preventScroll: true });
    }

    results.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  function getFocusableElements(container) {
    return Array.from(
      container.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
      )
    ).filter((element) => !element.hidden && element.offsetParent !== null);
  }

  // 6. 移动端筛选抽屉
  function openDrawer() {
    if (!panel || !mobileMedia.matches) {
      panel?.scrollIntoView({ behavior: "smooth", block: "start" });
      return;
    }

    lastFocusedElement = document.activeElement;
    uiState.drawerOpen = true;
    panel.classList.add("is-drawer-open");
    panel.setAttribute("aria-modal", "true");
    panel.setAttribute("role", "dialog");
    drawerOpenButton?.setAttribute("aria-expanded", "true");

    if (drawerBackdrop) {
      drawerBackdrop.hidden = false;
      window.requestAnimationFrame(() => drawerBackdrop.classList.add("is-visible"));
    }

    document.body.classList.add("pfl-filter-drawer-open");
    window.setTimeout(() => drawerCloseButton?.focus(), 30);
  }

  function closeDrawer({ viewResults = false } = {}) {
    if (!panel || !uiState.drawerOpen) {
      if (viewResults) {
        scrollToResults(false);
      }
      return;
    }

    uiState.drawerOpen = false;
    panel.classList.remove("is-drawer-open");
    panel.removeAttribute("aria-modal");
    panel.removeAttribute("role");
    drawerOpenButton?.setAttribute("aria-expanded", "false");
    drawerBackdrop?.classList.remove("is-visible");
    document.body.classList.remove("pfl-filter-drawer-open");

    window.setTimeout(() => {
      if (drawerBackdrop) {
        drawerBackdrop.hidden = true;
      }

      if (viewResults) {
        scrollToResults(false);
      } else if (lastFocusedElement instanceof HTMLElement) {
        lastFocusedElement.focus();
      }
    }, 220);
  }

  function initializeGroups() {
    safelyReadUiState();

    filterGroups.forEach((group) => {
      const key = group.dataset.filterGroup;
      const defaultOpen = group.dataset.defaultOpen === "1";
      const hasSelected = Boolean(group.querySelector("input:checked"));
      const expanded = hasSelected || (
        uiState.hasSavedState
          ? uiState.expandedGroups.has(key)
          : defaultOpen
      );

      setGroupExpanded(group, expanded, false);

      const toggle = group.querySelector(".filter-group-toggle");
      toggle?.addEventListener("click", () => {
        setGroupExpanded(group, !group.classList.contains("is-open"));
      });

      const moreButton = group.querySelector("[data-filter-options-more]");
      moreButton?.addEventListener("click", () => {
        if (uiState.expandedOptions.has(key)) {
          uiState.expandedOptions.delete(key);
        } else {
          uiState.expandedOptions.add(key);
        }

        saveUiState();
        orderAndLimitGroupOptions(group);
      });

      const searchInput = group.querySelector("[data-filter-option-search]");
      const clearSearch = group.querySelector("[data-clear-option-search]");

      searchInput?.addEventListener("input", () => {
        clearSearch?.classList.toggle("is-visible", searchInput.value.length > 0);
        orderAndLimitGroupOptions(group);
      });

      clearSearch?.addEventListener("click", () => {
        searchInput.value = "";
        clearSearch.classList.remove("is-visible");
        orderAndLimitGroupOptions(group);
        searchInput.focus();
      });
    });

    saveUiState();
  }

  initializeGroups();

  filterInputs.forEach((input) => {
    input.addEventListener("change", () => {
      refreshUI();
      scheduleAjaxUpdate();
    });
  });

  sortSelect?.addEventListener("change", () => {
    refreshUI();
    scheduleAjaxUpdate();
  });

  groupClearButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const group = form.querySelector(`[data-filter-group="${button.dataset.clearFilterGroup}"]`);

      group?.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach((input) => {
        input.checked = false;
      });

      refreshUI();
      scheduleAjaxUpdate();
    });
  });

  [clearAllButton, mobileClearButton, stickyClearButton].forEach((button) => {
    button?.addEventListener("click", () => {
      clearAllControls();
      scheduleAjaxUpdate();
    });
  });

  drawerOpenButton?.addEventListener("click", openDrawer);
  drawerCloseButton?.addEventListener("click", () => closeDrawer());
  drawerBackdrop?.addEventListener("click", () => closeDrawer());
  stickyModifyButton?.addEventListener("click", openDrawer);
  mobileViewButton?.addEventListener("click", () => closeDrawer({ viewResults: true }));

  ajaxRetryButton?.addEventListener("click", () => {
    hideAjaxError();
    requestProducts(lastRequestOptions || { page: getCurrentPageFromUrl(), historyMode: "replace" });
  });

  form.addEventListener("submit", (event) => {
    if (!ajaxEnabled) {
      if (sortSelect && !sortSelect.value) {
        sortSelect.disabled = true;
      }
      return;
    }

    event.preventDefault();
    requestProducts({ page: 1, historyMode: "push", focusResults: true });
  });

  document.addEventListener("click", (event) => {
    const paginationLink = event.target.closest("#product-results a[data-product-page]");

    if (paginationLink && ajaxEnabled) {
      event.preventDefault();
      requestProducts({
        page: Number.parseInt(paginationLink.dataset.productPage || "1", 10) || 1,
        historyMode: "push",
        focusResults: true
      });
      return;
    }

    const resetLink = event.target.closest("[data-filter-reset]");

    if (resetLink && ajaxEnabled) {
      event.preventDefault();
      clearAllControls();
      requestProducts({
        page: 1,
        historyMode: "push",
        focusResults: resetLink.closest("#product-results") !== null
      });
    }
  });

  document.addEventListener("keydown", (event) => {
    if (!uiState.drawerOpen) {
      return;
    }

    if (event.key === "Escape") {
      event.preventDefault();
      closeDrawer();
      return;
    }

    if (event.key !== "Tab" || !panel) {
      return;
    }

    const focusable = getFocusableElements(panel);

    if (focusable.length === 0) {
      return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  mobileMedia.addEventListener?.("change", (event) => {
    if (!event.matches && uiState.drawerOpen) {
      closeDrawer();
    }
  });

  if (ajaxEnabled) {
    window.addEventListener("popstate", () => {
      syncFormFromUrl(window.location.href);
      appliedState = serializeState();
      requestProducts({
        page: getCurrentPageFromUrl(),
        historyMode: "none",
        focusResults: false
      });
    });
  }

  refreshUI();
});
