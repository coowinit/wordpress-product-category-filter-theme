document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("productFilterForm");

  if (!form) {
    return;
  }

  const panel = document.getElementById("product-filter-panel");
  const filterInputs = Array.from(
    form.querySelectorAll(
      'input[type="checkbox"][data-filter-label], input[type="radio"][data-filter-label]'
    )
  );
  const sortSelect = form.querySelector("select[data-filter-sort]");
  const selectedTags = document.getElementById("selectedFilterTags");
  const stateTitle = document.getElementById("filterStateTitle");
  const stateHint = document.getElementById("filterStateHint");
  const clearAllButton = document.getElementById("clearAllFilters");
  const applyButton = document.getElementById("applyFilterButton");
  const groupClearButtons = Array.from(
    form.querySelectorAll("[data-clear-filter-group]")
  );

  function serializeState() {
    const values = [];

    filterInputs.forEach((input) => {
      if (input.checked) {
        values.push(`${input.name}=${input.value}`);
      }
    });

    if (sortSelect && sortSelect.value) {
      values.push(`${sortSelect.name}=${sortSelect.value}`);
    }

    return values.sort().join("&");
  }

  const initialState = serializeState();

  function getSelectedFilterCount() {
    return filterInputs.filter((input) => input.checked).length;
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
        ? group.querySelector('input[type="checkbox"]:checked, input[type="radio"]:checked')
        : null;

      button.hidden = !checked;
    });
  }

  function removeInput(input) {
    input.checked = false;
    refreshUI();
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
        text.textContent = `${input.dataset.filterLabel}：${input.dataset.optionLabel}`;

        const removeButton = document.createElement("button");
        removeButton.type = "button";
        removeButton.textContent = "×";
        removeButton.setAttribute(
          "aria-label",
          `移除 ${input.dataset.filterLabel} ${input.dataset.optionLabel}`
        );
        removeButton.addEventListener("click", () => removeInput(input));

        tag.appendChild(text);
        tag.appendChild(removeButton);
        selectedTags.appendChild(tag);
      });
  }

  function renderStateMessage() {
    const selectedCount = getSelectedFilterCount();
    const pending = serializeState() !== initialState;

    if (panel) {
      panel.classList.toggle("has-pending-changes", pending);
    }

    if (stateTitle) {
      if (pending) {
        stateTitle.textContent = selectedCount > 0
          ? `已选择 ${selectedCount} 个条件，尚未应用`
          : "筛选或排序已修改，尚未应用";
      } else {
        stateTitle.textContent = selectedCount > 0
          ? `当前已应用 ${selectedCount} 个筛选条件`
          : "当前未应用筛选条件";
      }
    }

    if (stateHint) {
      stateHint.textContent = pending
        ? "筛选或排序已经修改，点击“应用修改并查看结果”后生效。"
        : "页面中的产品数量与列表已经按照当前 URL 参数查询。";
    }

    if (applyButton) {
      applyButton.textContent = pending
        ? "应用修改并查看结果"
        : "查看筛选结果";
    }

    if (clearAllButton) {
      const hasSort = Boolean(sortSelect && sortSelect.value);
      clearAllButton.hidden = selectedCount === 0 && !hasSort;
    }
  }

  function refreshUI() {
    refreshOptionClasses();
    refreshGroupClearButtons();
    renderSelectedTags();
    renderStateMessage();
  }

  filterInputs.forEach((input) => {
    input.addEventListener("change", refreshUI);
  });

  if (sortSelect) {
    sortSelect.addEventListener("change", refreshUI);
  }

  groupClearButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const groupKey = button.dataset.clearFilterGroup;
      const group = form.querySelector(`[data-filter-group="${groupKey}"]`);

      if (!group) {
        return;
      }

      group.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach((input) => {
        input.checked = false;
      });

      refreshUI();
    });
  });

  if (clearAllButton) {
    clearAllButton.addEventListener("click", () => {
      filterInputs.forEach((input) => {
        input.checked = false;
      });

      if (sortSelect) {
        sortSelect.value = "";
      }

      refreshUI();
    });
  }

  form.addEventListener("submit", () => {
    if (sortSelect && !sortSelect.value) {
      sortSelect.disabled = true;
    }
  });

  refreshUI();
});
