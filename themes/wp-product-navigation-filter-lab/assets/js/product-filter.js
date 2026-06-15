document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("productFilterForm");

  if (!form) {
    return;
  }

  const filterInputs = Array.from(
    form.querySelectorAll(
      'input[type="checkbox"][data-filter-label], input[type="radio"][data-filter-label]'
    )
  );

  const selectedBar = document.getElementById("selectedFilterBar");
  const selectedTags = document.getElementById("selectedFilterTags");
  const clearAllButton = document.getElementById("clearAllFilters");

  function refreshOptionClasses() {
    form.querySelectorAll(".filter-option").forEach((label) => {
      const input = label.querySelector("input");
      label.classList.toggle("is-selected", Boolean(input && input.checked));
    });
  }

  function removeInput(input) {
    input.checked = false;
    refreshUI();
  }

  function renderSelectedTags() {
    if (!selectedBar || !selectedTags) {
      return;
    }

    selectedTags.innerHTML = "";

    const checkedInputs = filterInputs.filter((input) => input.checked);
    selectedBar.hidden = checkedInputs.length === 0;

    checkedInputs.forEach((input) => {
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

      removeButton.addEventListener("click", () => removeInput(input));

      tag.appendChild(text);
      tag.appendChild(removeButton);
      selectedTags.appendChild(tag);
    });
  }

  function refreshUI() {
    refreshOptionClasses();
    renderSelectedTags();
  }

  filterInputs.forEach((input) => {
    input.addEventListener("change", refreshUI);
  });

  if (clearAllButton) {
    clearAllButton.addEventListener("click", () => {
      filterInputs.forEach((input) => {
        input.checked = false;
      });

      refreshUI();
    });
  }

  refreshUI();
});
