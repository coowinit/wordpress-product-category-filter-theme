document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("productFilterForm");

  if (!form) {
    return;
  }

  const config = window.pflProductFilter || null;
  const ajaxEnabled = Boolean(
    config
    && config.ajaxUrl
    && config.nonce
    && window.fetch
    && window.URL
    && window.URLSearchParams
    && window.history
  );

  const panel = document.getElementById("product-filter-panel");
  const countElement = document.getElementById("productFilterResultCount");
  const liveRegion = document.getElementById("productFilterLiveRegion");
  const selectedTags = document.getElementById("selectedFilterTags");
  const stateTitle = document.getElementById("filterStateTitle");
  const stateHint = document.getElementById("filterStateHint");
  const clearAllButton = document.getElementById("clearAllFilters");
  const applyButton = document.getElementById("applyFilterButton");
  const sortSelect = form.querySelector("select[data-filter-sort]");

  const filterInputs = Array.from(
    form.querySelectorAll(
      'input[type="checkbox"][data-filter-label], input[type="radio"][data-filter-label]'
    )
  );

  const groupClearButtons = Array.from(
    form.querySelectorAll("[data-clear-filter-group]")
  );

  let appliedState = serializeState();
  let requestController = null;
  let requestNumber = 0;
  let debounceTimer = null;
  let isLoading = false;

  if (ajaxEnabled) {
    document.documentElement.classList.add("pfl-ajax-enabled");

    if (!history.state || !history.state.pflProductFilter) {
      history.replaceState(
        { pflProductFilter: true },
        "",
        window.location.href
      );
    }
  }

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

    params.forEach((value, key) => {
      url.searchParams.append(key, value);
    });

    if (page > 1) {
      url.searchParams.set("paged", String(page));
    }

    return url.toString();
  }

  function getValuesForInputFromUrl(input, url) {
    const exactName = input.name;
    const baseName = exactName.endsWith("[]")
      ? exactName.slice(0, -2)
      : exactName;

    const values = [];

    url.searchParams.forEach((value, key) => {
      if (
        key === exactName
        || key === baseName
        || key.startsWith(`${baseName}[`)
      ) {
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
    });

    if (sortSelect) {
      sortSelect.value = url.searchParams.get("sort") || "";
    }

    refreshUI();
  }

  function refreshOptionClasses() {
    form.querySelectorAll(".filter-option").forEach((label) => {
      const input = label.querySelector("input");
      label.classList.toggle("is-selected", Boolean(input && input.checked));
    });
  }

  function refreshGroupClearButtons() {
    groupClearButtons.forEach((button) => {
      const groupKey = button.dataset.clearFilterGroup;
      const group = form.querySelector(`[data-filter-group="${groupKey}"]`);
      const checked = group
        ? group.querySelector(
          'input[type="checkbox"]:checked, input[type="radio"]:checked'
        )
        : null;

      button.hidden = !checked;
    });
  }

  function renderSelectedTags() {
    if (!selectedTags) {
      return;
    }

    selectedTags.innerHTML = "";

    filterInputs
      .filter((input) => input.checked)
      .forEach((input) => {
        const tag = document.createElement("span");
        tag.className = "selected-filter-tag";

        const text = document.createElement("span");
        text.textContent =
          `${input.dataset.filterLabel}：${input.dataset.optionLabel}`;

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

        tag.appendChild(text);
        tag.appendChild(removeButton);
        selectedTags.appendChild(tag);
      });
  }

  function renderStateMessage() {
    const selectedCount = getSelectedFilterCount();
    const pending = serializeState() !== appliedState;

    if (panel) {
      panel.classList.toggle("has-pending-changes", pending && !isLoading);
      panel.classList.toggle("is-loading", isLoading);
    }

    if (stateTitle) {
      if (isLoading) {
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
      if (isLoading) {
        stateHint.textContent = "正在通过 AJAX 请求服务器，无需刷新整个页面。";
      } else if (pending && ajaxEnabled) {
        stateHint.textContent = "筛选或排序发生变化，系统将自动更新产品列表。";
      } else if (ajaxEnabled) {
        stateHint.textContent = "结果已同步到地址栏，可复制链接或使用浏览器前进、后退。";
      } else {
        stateHint.textContent = pending
          ? "点击“查看筛选结果”后生效。"
          : "页面中的产品数量与列表已经按照当前 URL 参数查询。";
      }
    }

    if (applyButton) {
      applyButton.disabled = isLoading;
      applyButton.textContent = isLoading
        ? "正在更新……"
        : (pending ? "立即更新结果" : "查看筛选结果");
    }
  }

  function refreshUI() {
    refreshOptionClasses();
    refreshGroupClearButtons();
    renderSelectedTags();
    renderStateMessage();
  }

  function announce(message) {
    if (!liveRegion) {
      return;
    }

    liveRegion.textContent = "";

    window.setTimeout(() => {
      liveRegion.textContent = message;
    }, 30);
  }

  function setLoading(loading) {
    isLoading = loading;

    const results = document.getElementById("product-results");

    if (results) {
      results.classList.toggle("is-loading", loading);
      results.setAttribute("aria-busy", loading ? "true" : "false");
    }

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
    if (countElement) {
      countElement.textContent = String(count);
    }
  }

  async function requestProducts({
    page = 1,
    historyMode = "push",
    focusResults = false,
    fallbackOnError = true
  } = {}) {
    if (!ajaxEnabled) {
      window.location.assign(buildClientStateUrl(page));
      return;
    }

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

      appliedState = serializeState();

      if (historyMode === "push") {
        history.pushState(
          { pflProductFilter: true },
          "",
          payload.data.url || targetUrl
        );
      } else if (historyMode === "replace") {
        history.replaceState(
          { pflProductFilter: true },
          "",
          payload.data.url || targetUrl
        );
      }

      setLoading(false);
      refreshUI();
      announce(
        payload.data.announcement
        || `筛选完成，共找到 ${payload.data.foundPosts} 个产品。`
      );

      if (focusResults) {
        const newResults = document.getElementById("product-results");

        if (newResults) {
          newResults.focus({ preventScroll: true });
          newResults.scrollIntoView({
            behavior: "smooth",
            block: "start"
          });
        }
      }
    } catch (error) {
      if (error.name === "AbortError") {
        return;
      }

      setLoading(false);

      if (fallbackOnError) {
        announce(
          config.messages?.error
          || "AJAX 请求失败，正在使用普通页面请求。"
        );
        window.location.assign(targetUrl);
      } else {
        window.location.reload();
      }
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
      requestProducts({
        page: 1,
        historyMode: "push",
        focusResults: false
      });
    }, Number(config.debounce) || 320);
  }

  function clearAllControls() {
    filterInputs.forEach((input) => {
      input.checked = false;
    });

    if (sortSelect) {
      sortSelect.value = "";
    }

    refreshUI();
  }

  filterInputs.forEach((input) => {
    input.addEventListener("change", () => {
      refreshUI();
      scheduleAjaxUpdate();
    });
  });

  if (sortSelect) {
    sortSelect.addEventListener("change", () => {
      refreshUI();
      scheduleAjaxUpdate();
    });
  }

  groupClearButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const groupKey = button.dataset.clearFilterGroup;
      const group = form.querySelector(`[data-filter-group="${groupKey}"]`);

      if (!group) {
        return;
      }

      group
        .querySelectorAll('input[type="checkbox"], input[type="radio"]')
        .forEach((input) => {
          input.checked = false;
        });

      refreshUI();
      scheduleAjaxUpdate();
    });
  });

  if (clearAllButton) {
    clearAllButton.addEventListener("click", () => {
      clearAllControls();
      scheduleAjaxUpdate();
    });
  }

  form.addEventListener("submit", (event) => {
    if (!ajaxEnabled) {
      if (sortSelect && !sortSelect.value) {
        sortSelect.disabled = true;
      }

      return;
    }

    event.preventDefault();

    requestProducts({
      page: 1,
      historyMode: "push",
      focusResults: true
    });
  });

  document.addEventListener("click", (event) => {
    const paginationLink = event.target.closest(
      "#product-results a[data-product-page]"
    );

    if (paginationLink && ajaxEnabled) {
      event.preventDefault();

      requestProducts({
        page: Number.parseInt(
          paginationLink.dataset.productPage || "1",
          10
        ) || 1,
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

  if (ajaxEnabled) {
    window.addEventListener("popstate", () => {
      syncFormFromUrl(window.location.href);
      appliedState = serializeState();

      requestProducts({
        page: getCurrentPageFromUrl(),
        historyMode: "none",
        focusResults: false,
        fallbackOnError: false
      });
    });
  }

  refreshUI();
});
