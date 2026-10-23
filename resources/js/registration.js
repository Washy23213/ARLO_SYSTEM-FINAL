document.addEventListener("DOMContentLoaded", function () {

  // Ensure checkboxes/radios use neutral accent until checked
  (function injectCheckboxStyles() {
    const css = `
      /* force neutral accent until checked, override other styles */
      input[type="checkbox"], input[type="radio"] {
        -webkit-appearance: auto !important;
        appearance: auto !important;
        accent-color: #9CA3AF !important; /* gray-400 */
        background-color: transparent !important;
        border-radius: 0 !important;
      }

      /* radios should appear circular */
      input[type="radio"] {
        border-radius: 50% !important;
      }

      /* when checked use green */
      input[type="checkbox"]:checked, input[type="radio"]:checked {
        accent-color: #02681e !important; /* CMU logo green when checked */
      }

      /* ensure unchecked controls aren't showing browser fill */
      input[type="checkbox"]:not(:checked), input[type="radio"]:not(:checked) {
        background-image: none !important;
      }

      input[data-registration-locked="true"],
      select[data-registration-locked="true"],
      textarea[data-registration-locked="true"] {
        background-color: #f3f4f6 !important;
        border-color: #d1d5db !important;
        color: #374151 !important;
        cursor: not-allowed !important;
        pointer-events: none !important;
      }

    `;
    const style = document.createElement('style');
    style.appendChild(document.createTextNode(css));
    document.head.appendChild(style);
  })();

  // ==================== MULTI-STEP FORM ====================
  const fieldsets = Array.from(document.querySelectorAll("fieldset.form-step"));
  const sectionGroups = [
    {
      title: "Verification",
      chip: "Verify",
      fieldsetIndexes: [0],
    },
    {
      title: "Set Account",
      chip: "Account",
      fieldsetIndexes: [1],
    },
    {
      title: "Personal and Contact Details",
      chip: "Profile",
      fieldsetIndexes: [2, 3],
    },
    {
      title: "Basic Education and Degree",
      chip: "Education",
      fieldsetIndexes: [4, 5],
    },
    {
      title: "Further Study",
      chip: "Further Study",
      fieldsetIndexes: [6, 7, 8],
    },
    {
      title: "Background and Support",
      chip: "Background",
      fieldsetIndexes: [9, 10],
    },
    {
      title: "Employment",
      chip: "Employment",
      fieldsetIndexes: [11, 12],
    },
    {
      title: "Comments and Suggestions",
      chip: "Feedback",
      fieldsetIndexes: [13],
    },
  ].filter((section) => section.fieldsetIndexes.some((index) => !!fieldsets[index]));
  const totalSections = sectionGroups.length;
  let currentStep = 0;
  let highestUnlockedStep = 0;
  let stepNavigationInProgress = false;
  const form = document.getElementById("multiStepForm");
  const emailInput = document.getElementById("email");
  const birthdateInput = document.getElementById("birthdate");
  const passwordInput = document.getElementById("password");
  const passwordHint = document.getElementById("passwordHint");
  const passwordConfirmationInput = document.getElementById("password_confirmation");
  const emailCheckMessage = document.getElementById("emailCheckMessage");
  const emailVerificationPanel = document.getElementById("emailVerificationPanel");
  const emailVerificationBackdrop = document.getElementById("emailVerificationBackdrop");
  const emailVerificationCloseBtn = document.getElementById("emailVerificationCloseBtn");
  const emailVerificationMiniTrigger = document.getElementById("emailVerificationMiniTrigger");
  const emailVerificationOpenBtn = document.getElementById("emailVerificationOpenBtn");
  const emailVerificationTitle = document.getElementById("emailVerificationTitle");
  const emailVerificationCopy = document.getElementById("emailVerificationCopy");
  const emailVerificationFooter = document.getElementById("emailVerificationFooter");
  const sendEmailVerificationBtn = document.getElementById("sendEmailVerificationBtn");
  const resendEmailVerificationBtn = document.getElementById("resendEmailVerificationBtn");
  const emailVerificationCodeGroup = document.getElementById("emailVerificationCodeGroup");
  const emailVerificationCodeInput = document.getElementById("emailVerificationCode");
  const emailVerificationOtp = document.getElementById("emailVerificationOtp");
  const emailVerificationOtpDigits = Array.from(document.querySelectorAll("[data-email-verification-digit]"));
  const verifyEmailCodeBtn = document.getElementById("verifyEmailCodeBtn");
  const emailVerificationMessage = document.getElementById("emailVerificationMessage");
  const emailVerificationTiming = document.getElementById("emailVerificationTiming");
  const accountPasswordLockedNotice = document.getElementById("accountPasswordLockedNotice");
  const accountPasswordFields = document.getElementById("accountPasswordFields");
  const verificationStatusMessage = document.getElementById("verificationStatusMessage");
  const verificationNextButton = document.getElementById("verificationNextBtn");
  const verificationDegreeSelect = document.getElementById("degreeGraduated");
  const undergraduateCollegeMatchMessage = document.getElementById("undergraduateCollegeMatchMessage");
  const undergraduateVerificationMessage = document.getElementById("undergraduateVerificationMessage");
  const nondegreeVerificationMessage = document.getElementById("nondegreeVerificationMessage");
  const dataPrivacyConsentInput = document.getElementById("data_privacy_consent");
  const privacyConsentOverlay = document.getElementById("privacyConsentOverlay");
  const privacyConsentStatus = document.getElementById("privacyConsentStatus");
  const privacyConsentAgreeBtn = document.getElementById("privacyConsentAgreeBtn");
  const reopenPrivacyConsentBtn = document.getElementById("reopenPrivacyConsentBtn");
  const submitAccountOverviewModal = document.getElementById("submitAccountOverviewModal");
  const submitAccountOverviewCloseBtn = document.getElementById("submitAccountOverviewClose");
  const submitAccountOverviewBackBtn = document.getElementById("submitAccountOverviewBack");
  const submitAccountOverviewConfirmBtn = document.getElementById("submitAccountOverviewConfirm");
  const submitAccountOverviewEmail = document.getElementById("submitAccountOverviewEmail");
  const submitAccountOverviewNewsletter = document.getElementById("submitAccountOverviewNewsletter");
  const submitAccountOverviewPassword = document.getElementById("submitAccountOverviewPassword");
  const submitAccountOverviewPasswordToggle = document.getElementById("submitAccountOverviewPasswordToggle");
  const newsletterOptInInput = document.getElementById("newsletter_opt_in");
  const verificationFieldNames = ["firstname", "middlename", "lastname", "birthdate", "studentIdnum", "collegeGraduated", "yearGraduated"];
  let emailCheckState = {
    status: "idle", // idle | checking | valid | invalid
    email: "",
    message: "",
  };
  let emailVerificationState = {
    status: "idle", // idle | sending | sent | verifying | verified | invalid
    sentEmail: "",
    verifiedEmail: "",
    message: "",
    expiresAt: null,
    resendAvailableAt: null,
  };
  let emailVerificationTimerId = null;
  let emailVerificationBusy = false;
  let emailVerificationMiniViewOpen = false;
  let emailCheckTimeoutId = null;
  let verificationCheckTimeoutId = null;
  let verificationRequestController = null;
  let verificationRequestToken = 0;
  let verificationState = {
    status: "idle", // idle | checking | valid | invalid
    signature: "",
    verifiedCollege: "",
    verifiedDegree: "",
    verifiedYear: "",
    verifiedProgramLevel: "",
  };
  let submitAccountOverviewConfirmed = false;
  let submitAccountOverviewPasswordVisible = false;
  let registrationSubmitInProgress = false;

  function getEquivalentSelectValue(select, value) {
    const normalizedValue = String(value || "").trim();
    if (!select || !normalizedValue) return "";

    if (selectHasOption(select, normalizedValue)) {
      return normalizedValue;
    }

    const normalizedProgramValue = normalizeVerificationProgramValue(normalizedValue);
    if (!normalizedProgramValue) {
      return "";
    }

    const matchedOption = Array.from(select.options).find((option) => {
      return normalizeVerificationProgramValue(option.value) === normalizedProgramValue;
    });

    return matchedOption?.value || "";
  }

  function syncLockedRegistrationFieldValue(element) {
    if (!element || element.dataset.registrationLocked !== "true") return;

    const lockedValue = element.dataset.registrationLockedValue ?? "";
    if (element.value !== lockedValue) {
      element.value = lockedValue;
    }
  }

  function setRegistrationFieldLocked(element, value = null) {
    if (
      !(element instanceof HTMLInputElement)
      && !(element instanceof HTMLSelectElement)
      && !(element instanceof HTMLTextAreaElement)
    ) {
      return;
    }

    const lockedValue = value === null || typeof value === "undefined"
      ? String(element.value || "")
      : String(value);

    if (!Object.prototype.hasOwnProperty.call(element.dataset, "registrationLockedOriginalTabindex")) {
      const originalTabindex = element.getAttribute("tabindex");
      element.dataset.registrationLockedOriginalTabindex = originalTabindex === null ? "__none__" : originalTabindex;
    }

    element.dataset.registrationLocked = "true";
    element.dataset.registrationLockedValue = lockedValue;
    element.setAttribute("tabindex", "-1");
    element.setAttribute("aria-readonly", "true");

    if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement) {
      const inputType = element instanceof HTMLInputElement ? (element.type || "").toLowerCase() : "";
      if (!["checkbox", "radio", "file", "hidden"].includes(inputType)) {
        element.readOnly = true;
      }
    }

    syncLockedRegistrationFieldValue(element);

    try { element.blur(); } catch (e) {}
  }

  function clearRegistrationFieldLock(element) {
    if (
      !(element instanceof HTMLInputElement)
      && !(element instanceof HTMLSelectElement)
      && !(element instanceof HTMLTextAreaElement)
    ) {
      return;
    }

    delete element.dataset.registrationLocked;
    delete element.dataset.registrationLockedValue;

    const originalTabindex = element.dataset.registrationLockedOriginalTabindex;
    if (typeof originalTabindex === "string") {
      if (originalTabindex === "__none__") {
        element.removeAttribute("tabindex");
      } else {
        element.setAttribute("tabindex", originalTabindex);
      }
      delete element.dataset.registrationLockedOriginalTabindex;
    }

    element.removeAttribute("aria-readonly");

    if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement) {
      const inputType = element instanceof HTMLInputElement ? (element.type || "").toLowerCase() : "";
      if (!["checkbox", "radio", "file", "hidden"].includes(inputType)) {
        element.readOnly = false;
      }
    }
  }

  function preventLockedRegistrationFieldInteraction(event) {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;

    const lockedField = target.closest("[data-registration-locked='true']");
    if (
      !(lockedField instanceof HTMLInputElement)
      && !(lockedField instanceof HTMLSelectElement)
      && !(lockedField instanceof HTMLTextAreaElement)
    ) {
      return;
    }

    if (event.type === "input" || event.type === "change") {
      syncLockedRegistrationFieldValue(lockedField);
      return;
    }

    if (event.type === "keydown" && ["Tab", "Shift", "Escape"].includes(event.key)) {
      return;
    }

    event.preventDefault();
    syncLockedRegistrationFieldValue(lockedField);

    try { lockedField.blur(); } catch (e) {}
  }

  ["input", "change", "keydown", "beforeinput", "mousedown", "pointerdown", "touchstart"].forEach((eventName) => {
    document.addEventListener(eventName, preventLockedRegistrationFieldInteraction, true);
  });

  function syncVerifiedIdentityFields(verification = {}) {
    [
      ["firstname", verification.firstname],
      ["middlename", verification.middlename],
      ["lastname", verification.lastname],
      ["collegeGraduated", verification.collegeGraduated],
      ["yearGraduated", verification.yearGraduated],
    ].forEach(([fieldId, fieldValue]) => {
      const field = document.getElementById(fieldId);
      if (!field) return;

      if (typeof fieldValue !== "undefined" && fieldValue !== null) {
        field.value = String(fieldValue);
      }

      clearRegistrationFieldLock(field);
    });
  }

  function clearVerifiedIdentityFieldLocks() {
    ["firstname", "middlename", "lastname", "collegeGraduated", "yearGraduated"].forEach((fieldId) => {
      clearRegistrationFieldLock(document.getElementById(fieldId));
    });
  }

  function syncPrivacyConsentGate() {
    if (!dataPrivacyConsentInput || !privacyConsentAgreeBtn) return;

    const consentAccepted = !!dataPrivacyConsentInput.checked;
    privacyConsentAgreeBtn.disabled = !consentAccepted;
    privacyConsentAgreeBtn.setAttribute("aria-disabled", consentAccepted ? "false" : "true");

    if (privacyConsentStatus) {
      privacyConsentStatus.classList.toggle("hidden", !consentAccepted);
    }
  }

  function syncRegistrationOverlayLock() {
    const shouldLockBody = (
      privacyConsentOverlay && !privacyConsentOverlay.classList.contains("hidden")
    ) || (
      submitAccountOverviewModal && !submitAccountOverviewModal.classList.contains("hidden")
    );

    document.body.style.overflow = shouldLockBody ? "hidden" : "";
  }

  function getMaskedSecret(value) {
    const normalizedValue = String(value || "");
    return normalizedValue ? "•".repeat(normalizedValue.length) : "Not set yet";
  }

  function syncSubmitAccountOverviewPreview() {
    if (!submitAccountOverviewModal) return;

    if (submitAccountOverviewEmail) {
      const email = (emailInput?.value || "").trim();
      submitAccountOverviewEmail.textContent = email || "Not set yet";
    }

    if (submitAccountOverviewNewsletter) {
      submitAccountOverviewNewsletter.textContent = newsletterOptInInput?.checked
        ? "Subscribed to OARL updates"
        : "Not subscribed";
    }

    const rawPassword = passwordInput?.value || "";

    if (submitAccountOverviewPassword) {
      submitAccountOverviewPassword.textContent = submitAccountOverviewPasswordVisible
        ? (rawPassword || "Not set yet")
        : getMaskedSecret(rawPassword);
    }

    if (submitAccountOverviewPasswordToggle) {
      const canRevealPassword = rawPassword.length > 0 && !registrationSubmitInProgress;
      submitAccountOverviewPasswordToggle.disabled = !canRevealPassword;
      submitAccountOverviewPasswordToggle.textContent = submitAccountOverviewPasswordVisible ? "Hide" : "Show";
      submitAccountOverviewPasswordToggle.setAttribute("aria-pressed", submitAccountOverviewPasswordVisible ? "true" : "false");
    }
  }

  function setSubmitAccountOverviewOpen(isOpen) {
    if (!submitAccountOverviewModal) return;

    submitAccountOverviewModal.classList.toggle("hidden", !isOpen);

    if (!isOpen) {
      submitAccountOverviewPasswordVisible = false;
      syncSubmitAccountOverviewPreview();
      syncRegistrationOverlayLock();

      if (!registrationSubmitInProgress) {
        const submitBtn = document.getElementById("submitBtn");
        if (submitBtn) {
          setTimeout(() => {
            try { submitBtn.focus(); } catch (e) {}
          }, 0);
        }
      }

      return;
    }

    submitAccountOverviewPasswordVisible = false;
    syncSubmitAccountOverviewPreview();
    syncRegistrationOverlayLock();

    const focusTarget = submitAccountOverviewConfirmBtn || submitAccountOverviewBackBtn || submitAccountOverviewCloseBtn;
    if (focusTarget) {
      setTimeout(() => {
        try { focusTarget.focus(); } catch (e) {}
      }, 0);
    }
  }

  function setPrivacyConsentOverlayOpen(isOpen) {
    if (!privacyConsentOverlay) return;

    privacyConsentOverlay.classList.toggle("hidden", !isOpen);
    syncRegistrationOverlayLock();

    if (isOpen) {
      if (privacyConsentStatus) privacyConsentStatus.classList.add("hidden");
      if (dataPrivacyConsentInput) {
        setTimeout(() => {
          try { dataPrivacyConsentInput.focus(); } catch (e) {}
        }, 0);
      }
      return;
    }

    syncPrivacyConsentGate();

    const firstnameInput = document.getElementById("firstname");
    if (firstnameInput) {
      setTimeout(() => {
        try { firstnameInput.focus(); } catch (e) {}
      }, 0);
    }
  }

  // ✅ Apply Tailwind styles to form controls automatically
  function applyTailwindToInputs() {
    const inputs = document.querySelectorAll("input, select, textarea");

    inputs.forEach((el) => {
      // Skip hidden inputs
      if (el.type === "hidden") return;

      // Base style for all inputs
      el.classList.add(
        "w-full",
        "px-3",
        "py-2",
        "border",
        "border-gray-300",
        "rounded-lg",
        "outline-none",
        "focus:ring-2",
        "focus:ring-green-600",
        "focus:border-green-600",
        "transition"
      );

      // Fix checkbox/radio width (do not make them full width)
      if (el.type === "checkbox" || el.type === "radio") {
        el.classList.remove("w-full", "px-3", "py-2");
        el.classList.add("w-4", "h-4");
        // radios should be circular; remove large rounding and add full rounding
        el.classList.remove("rounded-lg");
        el.classList.add("rounded-full");
        // remove focus ring / green focus classes for checkboxes/radios
        el.classList.remove("focus:ring-2", "focus:ring-green-600", "focus:border-green-600");
      }

      if (el.tagName === "SELECT") {
        el.classList.add("registration-select");
      }
    });
  }

  // ✅ Apply Tailwind to buttons
  function applyTailwindToButtons() {
    document.querySelectorAll(".next-btn").forEach((btn) => {
      btn.classList.add(
        "bg-green-700",
        "text-white",
        "px-6",
        "py-2",
        "rounded-lg",
        "hover:bg-green-800",
        "transition",
        "font-semibold"
      );
    });

    document.querySelectorAll(".previous-btn").forEach((btn) => {
      btn.classList.add(
        "bg-gray-500",
        "text-white",
        "px-6",
        "py-2",
        "rounded-lg",
        "hover:bg-gray-600",
        "transition",
        "font-semibold"
      );
    });
  }

  function getDirectRadioOptions(group) {
    return Array.from(group.children).filter((child) =>
      child instanceof HTMLElement && child.classList.contains("registration-radio-option")
    );
  }

  function isCompactRadioGroup(group) {
    if (!(group instanceof HTMLElement) || !group.classList.contains("registration-radio-group")) {
      return false;
    }

    const options = getDirectRadioOptions(group);
    if (options.length < 2 || options.length > 3) {
      return false;
    }

    return options.every((option) => !!option.querySelector('input[type="radio"]'));
  }

  function syncCompactRadioGroupLayout(root = document) {
    root.querySelectorAll(".registration-radio-group").forEach((group) => {
      const shouldUseCompactLayout = isCompactRadioGroup(group);
      group.classList.toggle("registration-radio-group--compact-options", shouldUseCompactLayout);

      getDirectRadioOptions(group).forEach((option) => {
        option.classList.toggle("registration-radio-option--compact", shouldUseCompactLayout);
      });
    });
  }

  function normalizeStep(step) {
    const parsedStep = Number.parseInt(step, 10);
    if (Number.isNaN(parsedStep)) return 0;
    if (parsedStep < 0) return 0;
    if (parsedStep >= totalSections) return Math.max(totalSections - 1, 0);
    return parsedStep;
  }

  function getFieldsetsForSection(step) {
    const section = sectionGroups[step];
    if (!section) return [];
    return section.fieldsetIndexes.map((index) => fieldsets[index]).filter(Boolean);
  }

  function getSectionIndexForFieldset(fieldsetIndex) {
    const sectionIndex = sectionGroups.findIndex((section) =>
      section.fieldsetIndexes.includes(fieldsetIndex)
    );
    return sectionIndex === -1 ? 0 : sectionIndex;
  }

  function sectionContainsElement(step, element) {
    if (!element) return false;
    return getFieldsetsForSection(step).some((fieldset) => fieldset.contains(element));
  }

  function getStepNavigationRow(fieldset) {
    if (!fieldset) return null;
    const actionButton = fieldset.querySelector(".next-btn, .previous-btn, #submitBtn");
    return actionButton ? actionButton.parentElement : null;
  }

  function updateStepChips(step) {
    const stepChipsEl = document.getElementById("stepChips");
    if (!stepChipsEl) return;

    stepChipsEl.innerHTML = "";

    sectionGroups.forEach((section, index) => {
      const chip = document.createElement("button");
      chip.type = "button";
      chip.classList.add("registration-step-chip");

      if (index < step) chip.classList.add("is-complete");
      if (index === step) chip.classList.add("is-active");
      if (index <= highestUnlockedStep) chip.classList.add("is-clickable");

      chip.disabled = index > highestUnlockedStep;
      chip.setAttribute("aria-current", index === step ? "step" : "false");
      chip.setAttribute("aria-label", `${section.title} section`);
      chip.dataset.stepIndex = String(index);

      chip.textContent = section.chip;
      stepChipsEl.appendChild(chip);
    });
  }

  function findFirstVisibleInput(fieldsetGroup) {
    for (const fieldset of fieldsetGroup) {
      const inputs = fieldset.querySelectorAll("input, select, textarea");

      for (const input of inputs) {
        if (input.disabled) continue;
        if (input.type === "hidden") continue;
        if (input.closest && input.closest(".hidden")) continue;
        if (input.getClientRects && input.getClientRects().length === 0) continue;

        return input;
      }
    }

    return null;
  }

  // ✅ Show section using Tailwind (hidden/block)
  function showStep(step) {
    currentStep = normalizeStep(step);
    const activeFieldsets = getFieldsetsForSection(currentStep);
    const lastVisibleFieldset = activeFieldsets[activeFieldsets.length - 1] || null;

    fieldsets.forEach((fs) => {
      const shouldShow = activeFieldsets.includes(fs);
      fs.classList.toggle("hidden", !shouldShow);
      fs.classList.toggle("block", shouldShow);

      const navRow = getStepNavigationRow(fs);
      if (navRow) {
        navRow.classList.toggle("hidden", shouldShow && fs !== lastVisibleFieldset);
      }
    });

    // Update progress UI: step text, step title, and progress bar
    const stepTextEl = document.getElementById("stepText");
    if (stepTextEl) stepTextEl.textContent = `Section ${currentStep + 1} of ${totalSections}`;

    const stepTitleEl = document.getElementById("stepTitle");
    if (stepTitleEl) {
      stepTitleEl.textContent = sectionGroups[currentStep]?.title || "";
    }

    const progressBar = document.getElementById("progressBar");
    if (progressBar) {
      const percent = totalSections ? Math.round(((currentStep + 1) / totalSections) * 100) : 0;
      progressBar.style.width = `${percent}%`;
    }

    updateStepChips(currentStep);

    // Accessibility: Focus first input
    const firstInput = findFirstVisibleInput(activeFieldsets);
    if (firstInput) firstInput.focus();
  }

  // Hide all fieldsets first (Tailwind way)
  fieldsets.forEach((fs) => {
    fs.classList.add("hidden");
  });

  showStep(currentStep);

  if (dataPrivacyConsentInput && privacyConsentAgreeBtn && privacyConsentOverlay) {
    dataPrivacyConsentInput.addEventListener("change", function () {
      syncPrivacyConsentGate();
      if (dataPrivacyConsentInput.checked) {
        clearInvalid(dataPrivacyConsentInput);
      }
    });

    privacyConsentAgreeBtn.addEventListener("click", function () {
      if (!dataPrivacyConsentInput.checked) return;
      setPrivacyConsentOverlayOpen(false);
    });

    if (reopenPrivacyConsentBtn) {
      reopenPrivacyConsentBtn.addEventListener("click", function () {
        setPrivacyConsentOverlayOpen(true);
      });
    }

    syncPrivacyConsentGate();
    setPrivacyConsentOverlayOpen(!dataPrivacyConsentInput.checked);
  }

  // ==================== VALIDATION ====================
  function getRadioValidationGroup(input) {
    if (!input || input.type !== "radio") return null;

    return input.closest(".registration-radio-group, .registration-rating-options");
  }

  function getFieldErrorKey(input) {
    if (!input) return "";
    if (input.type === "radio" && input.name) return `radio:${input.name}`;

    return input.id || input.name || "";
  }

  function getFieldErrorScope(input) {
    const radioGroup = getRadioValidationGroup(input);
    if (radioGroup && radioGroup.parentNode) return radioGroup.parentNode;

    return input && input.parentNode ? input.parentNode : null;
  }

  function getFieldErrorAnchor(input) {
    return getRadioValidationGroup(input) || input;
  }

  function findFieldError(scope, fieldKey) {
    if (!scope || !fieldKey) return null;

    return Array.from(scope.querySelectorAll("[data-field-error-for]")).find((errorEl) =>
      errorEl.getAttribute("data-field-error-for") === fieldKey
    ) || null;
  }

  function removeDuplicateFieldErrors(scope, fieldKey, keep = null) {
    if (!scope || !fieldKey) return;

    Array.from(scope.querySelectorAll("[data-field-error-for]")).forEach((errorEl) => {
      if (errorEl === keep) return;
      if (errorEl.getAttribute("data-field-error-for") === fieldKey) {
        errorEl.remove();
      }
    });
  }

  function getOrCreateFieldError(input) {
    if (!input) return null;

    const fieldKey = getFieldErrorKey(input);
    if (!fieldKey) return null;

    const scope = getFieldErrorScope(input);
    const anchor = getFieldErrorAnchor(input);
    if (!scope || !anchor) return null;

    const radioGroup = getRadioValidationGroup(input);
    let errorEl = findFieldError(scope, fieldKey);

    if (radioGroup && errorEl && errorEl.parentNode !== scope) {
      errorEl.remove();
      errorEl = null;
    }

    if (!errorEl) {
      errorEl = document.createElement("p");
      errorEl.setAttribute("data-field-error-for", fieldKey);
      errorEl.classList.add("mt-1", "text-xs", "text-red-600");
      if (radioGroup) {
        errorEl.classList.add("registration-radio-error");
      }
      anchor.insertAdjacentElement("afterend", errorEl);
    }

    removeDuplicateFieldErrors(scope, fieldKey, errorEl);

    return errorEl;
  }

  function setFieldError(input, message) {
    const errorEl = getOrCreateFieldError(input);
    if (!errorEl) return;
    errorEl.textContent = message || "";
    errorEl.classList.toggle("hidden", !message);
  }

  function clearFieldError(input) {
    const fieldKey = getFieldErrorKey(input);
    const scope = getFieldErrorScope(input);
    if (!fieldKey || !scope) return;

    let keptError = false;
    Array.from(scope.querySelectorAll("[data-field-error-for]")).forEach((errorEl) => {
      if (errorEl.getAttribute("data-field-error-for") !== fieldKey) return;

      if (!keptError) {
        errorEl.textContent = "";
        errorEl.classList.add("hidden");
        keptError = true;
      } else {
        errorEl.remove();
      }
    });
  }

  function setInlineGroupError(container, fieldKey, message) {
    if (!container || !fieldKey) return;

    const scope = container.parentElement || container;
    let errorEl = Array.from(scope.querySelectorAll("[data-registration-group-error-for]")).find((element) =>
      element.getAttribute("data-registration-group-error-for") === fieldKey
    );

    if (!errorEl) {
      errorEl = document.createElement("p");
      errorEl.setAttribute("data-registration-group-error-for", fieldKey);
      errorEl.setAttribute("role", "alert");
      errorEl.className = "mt-2 text-xs text-red-600";
      container.insertAdjacentElement("afterend", errorEl);
    }

    errorEl.textContent = message || "";
    errorEl.classList.toggle("hidden", !message);
  }

  function clearInlineGroupError(container, fieldKey) {
    if (!container || !fieldKey) return;

    const scope = container.parentElement || container;
    Array.from(scope.querySelectorAll("[data-registration-group-error-for]")).forEach((errorEl) => {
      if (errorEl.getAttribute("data-registration-group-error-for") === fieldKey) {
        errorEl.remove();
      }
    });
  }

  function markInvalid(input, message = "") {
    input.classList.remove("border-gray-300");
    input.classList.add("border-red-500", "ring-2", "ring-red-200");
    if (input === emailVerificationCodeInput) {
      // The OTP input overlays a six-column digit grid. Inserting a generic
      // field error beside it makes the message occupy one narrow grid cell;
      // the verification status message below the code row already presents it
      // at full width.
      clearFieldError(input);
      syncEmailVerificationOtpDisplay();
      return;
    }
    const checkboxGroup = input.type === "checkbox" && input.closest(".registration-check-grid");
    if (checkboxGroup) {
      setInlineGroupError(checkboxGroup, input.name || input.id, message);
      return;
    }
    if (message) setFieldError(input, message);
  }

  function clearInvalid(input) {
    input.classList.remove("border-red-500", "ring-2", "ring-red-200");
    input.classList.add("border-gray-300");
    clearFieldError(input);
    const checkboxGroup = input.type === "checkbox" && input.closest(".registration-check-grid");
    if (checkboxGroup) {
      clearInlineGroupError(checkboxGroup, input.name || input.id);
    }
    if (input === emailVerificationCodeInput) {
      syncEmailVerificationOtpDisplay();
    }
  }

  function syncEmailVerificationOtpDisplay() {
    if (!emailVerificationOtp) return;

    const code = (emailVerificationCodeInput?.value || "").replace(/\D/g, "").slice(0, 6);
    const hasFocus = document.activeElement === emailVerificationCodeInput;

    emailVerificationOtpDigits.forEach((digit, index) => {
      digit.textContent = code[index] || "";
      digit.classList.toggle("is-current", hasFocus && code.length < 6 && index === code.length);
    });
    emailVerificationOtp.classList.toggle(
      "is-invalid",
      !!emailVerificationCodeInput?.classList.contains("border-red-500")
    );
  }

  function hasValue(input) {
    return !!(input.value && input.value.trim());
  }

  function getMiddleNameValidationMessage(value) {
    const normalizedValue = String(value || "").trim();
    if (!normalizedValue) return "";

    const letters = normalizedValue.match(/[A-Za-z]/g) || [];
    return letters.length >= 2
      ? ""
      : "Please enter your full middle name.";
  }

  function getBirthdateValidationMessage(input) {
    const value = String(input?.value || "").trim();
    if (!value) return "";

    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    if (!match) {
      return "Enter the birth year using four digits.";
    }

    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);
    const leapYear = year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0);
    const daysInMonth = [31, leapYear ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    if (year < 1 || month < 1 || month > 12 || day < 1 || day > daysInMonth[month - 1]) {
      return "Please enter a valid birthdate using a four-digit year.";
    }

    if ((input.max && value > input.max) || input.validity.rangeOverflow) {
      return "Birthdate cannot be in the future.";
    }

    return "";
  }

  function normalizeLocationValue(value) {
    return String(value || "")
      .toLowerCase()
      .replace(/\bcity\b/g, "")
      .replace(/[–—-]/g, " ")
      .replace(/[.,()]/g, " ")
      .replace(/\s+/g, " ")
      .trim();
  }

  function isRegionXSelection(value) {
    const normalized = normalizeLocationValue(value);

    return /\bregion\s*x\b/.test(normalized)
      || /\bregion\s*10\b/.test(normalized)
      || normalized.includes("northern mindanao")
      || regionXProvinceTokens.has(normalized);
  }

  function getRegionXMunicipalityMatch(value) {
    const normalized = normalizeLocationValue(value);
    if (!normalized) return "";

    return regionXMunicipalityOptions.find((option) => normalizeLocationValue(option) === normalized) || "";
  }

  function setupLiveRequiredValidation() {
    const requiredControls = document.querySelectorAll("input[required], select[required], textarea[required]");

    requiredControls.forEach((input) => {
      if (input.type === "radio") {
        input.addEventListener("change", function () {
          const group = document.querySelectorAll(`input[name="${input.name}"]`);
          const checked = document.querySelector(`input[name="${input.name}"]:checked`);
          if (checked) {
            group.forEach((item) => clearInvalid(item));
          } else {
            group.forEach((item) => markInvalid(item));
            markInvalid(input, "Please select an option.");
          }
        });
        return;
      }

      if (input.type === "checkbox") {
        const validateCurrentCheckbox = function () {
          if (input.disabled) return;
          if (input.closest && input.closest(".hidden")) return;
          if (!input.hasAttribute("required")) return;

          if (!input.checked) {
            markInvalid(input, "Please check this box to continue.");
          } else {
            clearInvalid(input);
          }
        };

        input.addEventListener("change", validateCurrentCheckbox);
        input.addEventListener("blur", validateCurrentCheckbox);
        return;
      }

      const validateCurrent = function () {
        if (input.disabled) return;
        if (input.type !== "hidden" && input.closest && input.closest(".hidden")) return;
        if (!input.hasAttribute("required")) return;

        if (input === birthdateInput) {
          const message = getBirthdateValidationMessage(input);
          input.setCustomValidity(message);
          if (message) {
            markInvalid(input, message);
            return;
          }
        }

        if (!hasValue(input)) {
          markInvalid(input, "This field is required.");
        } else {
          clearInvalid(input);
        }
      };

      input.addEventListener("blur", validateCurrent);
      input.addEventListener("input", validateCurrent);
      input.addEventListener("change", validateCurrent);
    });
  }

  function validateStep(step, opts = {}) {
    const currentFieldset = fieldsets[step];
    if (!currentFieldset) {
      return { ok: true, firstInvalid: null, message: "" };
    }
    const inputs = currentFieldset.querySelectorAll("input, select, textarea");

    for (let input of inputs) {
      // Skip disabled or hidden inputs (hidden via `.hidden` class or not rendered)
      if (input.disabled) continue;
      if (input.type !== 'hidden' && input.closest && input.closest('.hidden')) continue;
      if (input.type !== 'hidden' && input.getClientRects && input.getClientRects().length === 0) continue;
      if (!input.hasAttribute("required")) continue;

      clearInvalid(input);

      if (input.type === "radio") {
        const checked = currentFieldset.querySelector(
          `input[name="${input.name}"]:checked`
        );
        if (!checked) {
          console.warn("Validation failed — missing required radio group:", input.name, input);
          markInvalid(input, "Please select an option.");
          return {
            ok: false,
            firstInvalid: input,
            message: "Please complete all required fields."
          };
        }
      } else if (input.type === "checkbox") {
        if (!input.checked) {
          markInvalid(input, "Please check this box to continue.");
          return {
            ok: false,
            firstInvalid: input,
            message: "Please complete all required fields."
          };
        }
      } else if (!input.value || !input.value.trim()) {
        console.warn("Validation failed — missing required field:", input.id || input.name || input);
        markInvalid(input, "This field is required.");
        return {
          ok: false,
          firstInvalid: input,
          message: "Please fill out all required fields."
        };
      }

      if (input === birthdateInput) {
        const birthdateMessage = getBirthdateValidationMessage(input);
        input.setCustomValidity(birthdateMessage);

        if (birthdateMessage) {
          markInvalid(input, birthdateMessage);
          return {
            ok: false,
            firstInvalid: input,
            message: birthdateMessage,
          };
        }
      }
    }

    const passwordInput = currentFieldset.querySelector('#password');
    const passwordConfirmationInput = currentFieldset.querySelector('#password_confirmation');
    if (passwordInput && passwordConfirmationInput) {
      if ((passwordInput.value || '').length < 8) {
        markInvalid(passwordInput, "Password must be at least 8 characters.");
        return {
          ok: false,
          firstInvalid: passwordInput,
          message: "Password must be at least 8 characters long."
        };
      }
      if (passwordInput.value !== passwordConfirmationInput.value) {
        markInvalid(passwordConfirmationInput, "Password confirmation does not match.");
        return {
          ok: false,
          firstInvalid: passwordConfirmationInput,
          message: "Password and Confirm Password do not match."
        };
      }
    }

    // Special validation for Professional Exams: if this fieldset contains
    // any `proExam[]` checkboxes require at least one to be selected.
    const proExamInputs = Array.from(currentFieldset.querySelectorAll('input[name="proExam[]"]')).filter((input) => {
      if (input.disabled) return false;
      if (input.closest && input.closest('.hidden')) return false;
      if (input.getClientRects && input.getClientRects().length === 0) return false;
      return true;
    });
    if (proExamInputs.length > 0) {
      const checkedBoxes = proExamInputs.filter((input) => input.checked);
      if (checkedBoxes.length === 0) {
        setInlineGroupError(proExamContainer, "proExam[]", "Select at least one professional examination.");
        return {
          ok: false,
          firstInvalid: proExamInputs[0],
          message: "Please select at least one professional examination."
        };
      }
      clearInlineGroupError(proExamContainer, "proExam[]");
    }

    const multiSelectCheckboxGroups = [
      {
        name: 'firstJobCompetencies[]',
        message: 'Please select at least one useful competency.'
      },
      {
        name: 'presentJobReasons[]',
        message: 'Please select at least one reason for accepting the present job.'
      }
    ];

    for (const group of multiSelectCheckboxGroups) {
      const groupInputs = Array.from(currentFieldset.querySelectorAll(`input[name="${group.name}"]`)).filter((input) => {
        if (input.disabled) return false;
        if (input.closest && input.closest('.hidden')) return false;
        if (input.getClientRects && input.getClientRects().length === 0) return false;
        return true;
      });

      if (groupInputs.length > 0) {
        const checkedBoxes = groupInputs.filter((input) => input.checked);
        if (checkedBoxes.length === 0) {
          markInvalid(groupInputs[0], group.message);
          return {
            ok: false,
            firstInvalid: groupInputs[0],
            message: group.message,
          };
        }
        clearInlineGroupError(groupInputs[0].closest(".registration-check-grid"), group.name);
      }
    }

    const middleNameInput = currentFieldset.querySelector('[name="middlename"]');
    if (
      middleNameInput
      && !middleNameInput.disabled
      && !(middleNameInput.closest && middleNameInput.closest(".hidden"))
      && !(middleNameInput.getClientRects && middleNameInput.getClientRects().length === 0)
    ) {
      const middleNameMessage = getMiddleNameValidationMessage(middleNameInput.value);
      if (middleNameMessage) {
        markInvalid(middleNameInput, middleNameMessage);
        return {
          ok: false,
          firstInvalid: middleNameInput,
          message: middleNameMessage,
        };
      }

      clearInvalid(middleNameInput);
    }

    const municipalityValidation = validateMunicipalityRegionSelection(currentFieldset);
    if (!municipalityValidation.ok) {
      return municipalityValidation;
    }

    const undergraduateCollegeValidation = validateUndergraduateCollegeMatch(currentFieldset);
    if (!undergraduateCollegeValidation.ok) {
      return undergraduateCollegeValidation;
    }

    return { ok: true, firstInvalid: null, message: "" };
  }

  function validateCurrentSection(step) {
    const sectionFieldsets = getFieldsetsForSection(step);

    for (const fieldset of sectionFieldsets) {
      const fieldsetIndex = fieldsets.indexOf(fieldset);
      const result = validateStep(fieldsetIndex);
      if (!result.ok) return result;
    }

    return { ok: true, firstInvalid: null, message: "" };
  }

  function ensureSavedMiniViewsForCurrentStep(step) {
    if (sectionContainsElement(step, firstJobFields)) {
      const wantsFirstJobExtended =
        getCheckedRadioValue("employedAfterGrad") === "Yes"
        && getCheckedRadioValue("shareMoreFirstJobDetails") === "Yes";

      if (wantsFirstJobExtended && !firstJobExtendedViewSaved) {
        firstJobExtendedViewOpen = true;
        syncFirstJobSection();
        focusFirstInputInContainer(firstJobExtendedFields);

        return {
          ok: false,
          container: firstJobExtendedFields,
          message: "Please save the additional first job details or choose No if you do not want to include them yet.",
        };
      }
    }

    if (sectionContainsElement(step, employmentDetails)) {
      const wantsEmploymentExtended =
        getCheckedRadioValue("presentlyEmployed") === "Yes"
        && getCheckedRadioValue("shareMoreEmploymentDetails") === "Yes";

      if (wantsEmploymentExtended && !employmentExtendedViewSaved) {
        employmentExtendedViewOpen = true;
        syncPresentEmploymentSection();
        focusFirstInputInContainer(employmentExtendedDetails);

        return {
          ok: false,
          container: employmentExtendedDetails,
          message: "Please save the additional work details or choose No if you do not want to include them yet.",
        };
      }
    }

    return { ok: true, message: "" };
  }

  function setEmailStatus(status, message = "") {
    emailCheckState.status = status;
    emailCheckState.message = message;

    if (!emailCheckMessage) return;

    emailCheckMessage.textContent = message;
    emailCheckMessage.classList.remove("text-gray-500", "text-green-700", "text-red-600");

    if (status === "valid") {
      emailCheckMessage.classList.add("text-green-700");
    } else if (status === "invalid") {
      emailCheckMessage.classList.add("text-red-600");
    } else {
      emailCheckMessage.classList.add("text-gray-500");
    }
  }

  function getCsrfToken() {
    return form?.querySelector('input[name="_token"]')?.value || "";
  }

  function getNormalizedAccountEmail() {
    return String(emailInput?.value || "").trim().toLowerCase();
  }

  function getEmailVerificationSendUrl() {
    return form?.dataset?.emailVerificationSendUrl || "/signup/email-verification/send";
  }

  function getEmailVerificationVerifyUrl() {
    return form?.dataset?.emailVerificationVerifyUrl || "/signup/email-verification/verify";
  }

  function isCurrentEmailVerified() {
    const email = getNormalizedAccountEmail();

    return !!email
      && emailVerificationState.status === "verified"
      && emailVerificationState.verifiedEmail === email;
  }

  function hasEmailVerificationCodeForCurrentEmail() {
    return !!getNormalizedAccountEmail()
      && emailVerificationState.sentEmail === getNormalizedAccountEmail();
  }

  function formatCountdown(seconds) {
    const safeSeconds = Math.max(0, Number.parseInt(seconds, 10) || 0);
    const minutes = Math.floor(safeSeconds / 60);
    const remainingSeconds = safeSeconds % 60;

    return `${minutes}:${String(remainingSeconds).padStart(2, "0")}`;
  }

  function secondsUntil(timestamp) {
    if (!timestamp) return 0;

    return Math.max(0, Math.ceil((timestamp - Date.now()) / 1000));
  }

  function timestampFromVerificationPayload(result, isoKey, secondsKey) {
    const isoValue = result?.[isoKey];
    if (isoValue) {
      const parsed = Date.parse(isoValue);
      if (!Number.isNaN(parsed)) {
        return parsed;
      }
    }

    const secondsValue = Number.parseInt(result?.[secondsKey], 10);
    if (!Number.isNaN(secondsValue) && secondsValue > 0) {
      return Date.now() + secondsValue * 1000;
    }

    return null;
  }

  function setEmailVerificationMessage(status, message = "") {
    emailVerificationState.status = status;
    emailVerificationState.message = message;

    if (!emailVerificationMessage) return;

    emailVerificationMessage.textContent = message;
    emailVerificationMessage.classList.remove("text-gray-600", "text-green-700", "text-red-600");
    emailVerificationPanel?.classList.remove("is-verified", "is-error", "is-active");

    if (status === "verified") {
      emailVerificationMessage.classList.add("text-green-700");
      emailVerificationPanel?.classList.add("is-verified");
    } else if (status === "invalid") {
      emailVerificationMessage.classList.add("text-red-600");
      emailVerificationPanel?.classList.add("is-error");
    } else {
      emailVerificationMessage.classList.add("text-gray-600");
      if (status !== "idle") {
        emailVerificationPanel?.classList.add("is-active");
      }
    }
  }

  function setEmailVerificationButtonLabel(button, label) {
    if (!button) return;

    const labelElement = button.querySelector("[data-email-verification-label]");
    if (labelElement) {
      labelElement.textContent = label;
      return;
    }

    button.textContent = label;
  }

  function setEmailVerificationTimingFromResponse(result = {}) {
    const expiresAt = timestampFromVerificationPayload(result, "expires_at", "expires_in_seconds");
    const resendAvailableAt = timestampFromVerificationPayload(result, "resend_available_at", "resend_after_seconds");

    if (expiresAt) {
      emailVerificationState.expiresAt = expiresAt;
    }

    if (resendAvailableAt) {
      emailVerificationState.resendAvailableAt = resendAvailableAt;
    }

    startEmailVerificationTimer();
    syncEmailVerificationTiming();
  }

  function stopEmailVerificationTimer() {
    if (!emailVerificationTimerId) return;

    clearInterval(emailVerificationTimerId);
    emailVerificationTimerId = null;
  }

  function startEmailVerificationTimer() {
    if (emailVerificationTimerId) return;

    emailVerificationTimerId = setInterval(syncEmailVerificationTiming, 1000);
  }

  function setEmailVerificationTimingItems(items = []) {
    if (!emailVerificationTiming) return;

    emailVerificationTiming.replaceChildren();

    items.forEach((item) => {
      const container = document.createElement("span");
      container.className = `registration-email-verify__timing-item registration-email-verify__timing-item--${item.type}`;

      const icon = document.createElement("i");
      icon.className = `bx ${item.icon}`;
      icon.setAttribute("aria-hidden", "true");

      const label = document.createElement("span");
      label.className = "registration-email-verify__timing-label";
      label.textContent = item.label;

      const value = document.createElement("span");
      value.className = "registration-email-verify__timing-value";
      value.textContent = item.value;

      container.append(icon, label, value);
      emailVerificationTiming.appendChild(container);
    });

    emailVerificationTiming.classList.toggle("hidden", items.length === 0);
  }

  function getAccountStepNextButton() {
    const accountFieldset = emailInput?.closest("fieldset");
    return accountFieldset?.querySelector(".next-btn") || null;
  }

  function setAccountStepNextEnabled(enabled) {
    const accountNextButton = getAccountStepNextButton();
    if (!accountNextButton) return;

    accountNextButton.disabled = !enabled;
    accountNextButton.classList.toggle("opacity-60", !enabled);
    accountNextButton.classList.toggle("cursor-not-allowed", !enabled);
    accountNextButton.setAttribute("aria-disabled", enabled ? "false" : "true");
    accountNextButton.title = enabled ? "" : "Verify your email code before continuing.";
  }

  function syncAccountPasswordGate(options = {}) {
    const { clearPasswords = false } = options;
    const unlocked = isCurrentEmailVerified();

    setAccountStepNextEnabled(unlocked);

    if (accountPasswordFields) {
      accountPasswordFields.classList.toggle("hidden", !unlocked);
    }

    if (accountPasswordLockedNotice) {
      accountPasswordLockedNotice.classList.toggle("hidden", unlocked);
    }

    if (!unlocked && clearPasswords) {
      [passwordInput, passwordConfirmationInput].forEach((input) => {
        if (!input) return;
        input.value = "";
        input.type = "password";
        clearInvalid(input);
      });

      document.querySelectorAll(".registration-password-toggle").forEach((button) => {
        button.textContent = "Show";
        button.setAttribute("aria-pressed", "false");
      });
    }
  }

  function syncEmailVerificationTiming() {
    const codeWasSentForCurrentEmail = emailVerificationState.sentEmail === getNormalizedAccountEmail();
    const isVerified = isCurrentEmailVerified();
    const expiresSeconds = codeWasSentForCurrentEmail ? secondsUntil(emailVerificationState.expiresAt) : 0;
    const resendSeconds = codeWasSentForCurrentEmail ? secondsUntil(emailVerificationState.resendAvailableAt) : 0;
    const timingItems = [];

    if (!isVerified && codeWasSentForCurrentEmail) {
      if (emailVerificationState.expiresAt) {
        timingItems.push({
          type: expiresSeconds > 0 ? "expires" : "expired",
          icon: expiresSeconds > 0 ? "bx-time-five" : "bx-error-circle",
          label: expiresSeconds > 0 ? "Expires in" : "Status",
          value: expiresSeconds > 0 ? formatCountdown(expiresSeconds) : "Expired",
        });
      }

    }

    setEmailVerificationTimingItems(timingItems);

    if (resendEmailVerificationBtn && !emailVerificationBusy) {
      const waitingToResend = codeWasSentForCurrentEmail && resendSeconds > 0 && !isVerified;
      resendEmailVerificationBtn.disabled = waitingToResend;
      setEmailVerificationButtonLabel(resendEmailVerificationBtn, waitingToResend
        ? `Resend code in ${formatCountdown(resendSeconds)}`
        : "Resend code");
    }

    if (verifyEmailCodeBtn && !emailVerificationBusy) {
      const codeExpired = codeWasSentForCurrentEmail
        && emailVerificationState.expiresAt
        && expiresSeconds <= 0
        && !isVerified;
      verifyEmailCodeBtn.disabled = !!codeExpired;
    }

    if (!codeWasSentForCurrentEmail || isVerified) {
      stopEmailVerificationTimer();
    } else if (emailVerificationState.expiresAt || emailVerificationState.resendAvailableAt) {
      startEmailVerificationTimer();
    }
  }

  function setEmailVerificationBusy(isBusy, action = "") {
    emailVerificationBusy = isBusy;

    [sendEmailVerificationBtn, resendEmailVerificationBtn, verifyEmailCodeBtn].forEach((button) => {
      if (!button) return;
      button.disabled = isBusy;
    });

    setEmailVerificationButtonLabel(sendEmailVerificationBtn, isBusy && action === "send" ? "Sending..." : "Send code");
    setEmailVerificationButtonLabel(resendEmailVerificationBtn, isBusy && action === "send" ? "Sending..." : "Resend code");
    setEmailVerificationButtonLabel(verifyEmailCodeBtn, isBusy && action === "verify" ? "Confirming..." : "Confirm");

    if (!isBusy) {
      syncEmailVerificationTiming();
    }

    syncAccountPasswordGate();
  }

  function setEmailVerificationMiniViewState(isOpen) {
    emailVerificationMiniViewOpen = !!isOpen;

    if (emailVerificationPanel) {
      emailVerificationPanel.classList.toggle("is-mini-view", emailVerificationMiniViewOpen);
      emailVerificationPanel.setAttribute("role", emailVerificationMiniViewOpen ? "dialog" : "region");
      emailVerificationPanel.setAttribute("aria-hidden", "false");
      if (emailVerificationMiniViewOpen) {
        emailVerificationPanel.setAttribute("aria-modal", "true");
      } else {
        emailVerificationPanel.removeAttribute("aria-modal");
      }
    }

    emailVerificationBackdrop?.classList.toggle("hidden", !emailVerificationMiniViewOpen);
    emailVerificationCloseBtn?.classList.toggle("hidden", !emailVerificationMiniViewOpen);
    document.body.classList.toggle("email-verification-modal-open", emailVerificationMiniViewOpen);
  }

  function openEmailVerificationMiniView() {
    const email = getNormalizedAccountEmail();
    if (!emailVerificationPanel || !email || emailVerificationState.sentEmail !== email || isCurrentEmailVerified()) {
      return;
    }

    setEmailVerificationMiniViewState(true);
    syncEmailVerificationPanel();
    window.requestAnimationFrame(() => {
      try { emailVerificationCodeInput?.focus(); } catch (e) {}
    });
  }

  function dismissEmailVerificationMiniView() {
    setEmailVerificationMiniViewState(false);
    syncEmailVerificationPanel();
    if (emailVerificationOpenBtn && !emailVerificationOpenBtn.classList.contains("hidden")) {
      try { emailVerificationOpenBtn.focus(); } catch (e) {}
    }
  }

  function resetEmailVerificationState(message = "") {
    stopEmailVerificationTimer();

    emailVerificationState = {
      status: "idle",
      sentEmail: "",
      verifiedEmail: "",
      message: "",
      expiresAt: null,
      resendAvailableAt: null,
    };
    setEmailVerificationMiniViewState(false);
    if (emailVerificationMiniTrigger) {
      emailVerificationMiniTrigger.classList.add("hidden");
      emailVerificationMiniTrigger.setAttribute("aria-hidden", "true");
    }

    if (form) {
      form.dataset.emailVerified = "false";
    }

    if (emailVerificationCodeInput) {
      emailVerificationCodeInput.value = "";
      clearInvalid(emailVerificationCodeInput);
      syncEmailVerificationOtpDisplay();
    }

    if (emailVerificationCodeGroup) {
      emailVerificationCodeGroup.classList.add("hidden");
    }
    if (emailVerificationFooter) {
      emailVerificationFooter.classList.add("hidden");
    }
    if (emailVerificationTitle) {
      emailVerificationTitle.textContent = "Verify your email";
    }
    if (emailVerificationCopy) {
      emailVerificationCopy.textContent = "We will send a 6-digit code to confirm this address.";
    }

    if (sendEmailVerificationBtn) {
      sendEmailVerificationBtn.classList.remove("hidden");
    }

    if (resendEmailVerificationBtn) {
      resendEmailVerificationBtn.classList.add("hidden");
    }

    setEmailVerificationMessage("idle", message);
    syncEmailVerificationTiming();
    syncAccountPasswordGate({ clearPasswords: true });
  }

  function syncEmailVerificationPanel() {
    if (!emailVerificationPanel) return;

    const email = getNormalizedAccountEmail();
    const shouldShow = !!email && emailCheckState.status === "valid";

    if (!shouldShow) {
      setEmailVerificationMiniViewState(false);
      emailVerificationPanel.classList.add("hidden");
      emailVerificationPanel.setAttribute("aria-hidden", "true");
      if (emailVerificationMiniTrigger) {
        emailVerificationMiniTrigger.classList.add("hidden");
        emailVerificationMiniTrigger.setAttribute("aria-hidden", "true");
      }
      syncAccountPasswordGate({ clearPasswords: true });
      return;
    }

    if (isCurrentEmailVerified()) {
      setEmailVerificationMiniViewState(false);
      emailVerificationPanel.classList.remove("hidden");
      emailVerificationPanel.setAttribute("aria-hidden", "false");
      if (emailVerificationMiniTrigger) {
        emailVerificationMiniTrigger.classList.add("hidden");
        emailVerificationMiniTrigger.setAttribute("aria-hidden", "true");
      }
      if (emailVerificationTitle) {
        emailVerificationTitle.textContent = "Email verified";
      }
      if (emailVerificationCopy) {
        emailVerificationCopy.textContent = "You can now set your password.";
      }
      if (emailVerificationCodeGroup) {
        emailVerificationCodeGroup.classList.add("hidden");
      }
      if (sendEmailVerificationBtn) {
        sendEmailVerificationBtn.classList.add("hidden");
      }
      if (resendEmailVerificationBtn) {
        resendEmailVerificationBtn.classList.add("hidden");
      }
      if (emailVerificationFooter) {
        emailVerificationFooter.classList.add("hidden");
      }
      setEmailVerificationMessage("verified", "Email verified.");
      syncEmailVerificationTiming();
      syncAccountPasswordGate();
      return;
    }

    const codeWasSent = emailVerificationState.sentEmail === email;

    if (codeWasSent) {
      setEmailVerificationMiniViewState(emailVerificationMiniViewOpen);
      emailVerificationPanel.classList.toggle("hidden", !emailVerificationMiniViewOpen);
      emailVerificationPanel.setAttribute("aria-hidden", emailVerificationMiniViewOpen ? "false" : "true");
      if (emailVerificationMiniTrigger) {
        emailVerificationMiniTrigger.classList.toggle("hidden", emailVerificationMiniViewOpen);
        emailVerificationMiniTrigger.setAttribute("aria-hidden", emailVerificationMiniViewOpen ? "true" : "false");
      }
    } else {
      setEmailVerificationMiniViewState(false);
      emailVerificationPanel.classList.remove("hidden");
      emailVerificationPanel.setAttribute("aria-hidden", "false");
      if (emailVerificationMiniTrigger) {
        emailVerificationMiniTrigger.classList.add("hidden");
        emailVerificationMiniTrigger.setAttribute("aria-hidden", "true");
      }
    }

    if (emailVerificationTitle) {
      emailVerificationTitle.textContent = codeWasSent ? "Confirmation" : "Verify your email";
    }
    if (emailVerificationCopy) {
      emailVerificationCopy.textContent = codeWasSent
        ? "Enter the 6-digit code we sent to your email."
        : "We will send a 6-digit code to confirm this address.";
    }
    if (emailVerificationFooter) {
      emailVerificationFooter.classList.toggle("hidden", !codeWasSent);
    }

    if (emailVerificationCodeGroup) {
      emailVerificationCodeGroup.classList.toggle("hidden", !codeWasSent);
    }

    if (sendEmailVerificationBtn) {
      sendEmailVerificationBtn.classList.toggle("hidden", codeWasSent);
    }

    if (resendEmailVerificationBtn) {
      resendEmailVerificationBtn.classList.toggle("hidden", !codeWasSent);
    }

    if (!emailVerificationState.message) {
      setEmailVerificationMessage("idle", "");
    }

    syncEmailVerificationTiming();
    syncAccountPasswordGate();
  }

  async function sendEmailVerificationCode() {
    if (!emailInput) return false;

    const email = emailInput.value.trim();
    const emailOk = await checkEmailAvailability(email);
    if (!emailOk) {
      markInvalid(emailInput);
      try { emailInput.focus(); } catch (e) {}
      return false;
    }

    setEmailVerificationBusy(true, "send");
    setEmailVerificationMessage("sending", "Sending verification code...");

    try {
      const response = await fetch(getEmailVerificationSendUrl(), {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Accept": "application/json",
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
          "X-CSRF-TOKEN": getCsrfToken(),
        },
        body: JSON.stringify({ email }),
      });

      const result = await response.json().catch(() => ({}));

      if (!response.ok) {
        const message = result?.errors
          ? Object.values(result.errors).flat()[0]
          : (result?.message || "Unable to send the verification code right now.");

        if (response.status === 429 && result?.resend_after_seconds) {
          emailVerificationState.sentEmail = getNormalizedAccountEmail();
          emailVerificationState.verifiedEmail = "";
          setEmailVerificationMiniViewState(true);
          if (form) {
            form.dataset.emailVerified = "false";
          }
          setEmailVerificationTimingFromResponse(result);
          setEmailVerificationMessage("sent", message);
          syncEmailVerificationPanel();
          return false;
        }

        setEmailVerificationMessage("invalid", message);
        markInvalid(emailInput, message);
        return false;
      }

      clearInvalid(emailInput);
      emailVerificationState.sentEmail = getNormalizedAccountEmail();
      emailVerificationState.verifiedEmail = "";
      setEmailVerificationMiniViewState(true);
      if (form) {
        form.dataset.emailVerified = "false";
      }

      if (emailVerificationCodeInput) {
        emailVerificationCodeInput.value = "";
        clearInvalid(emailVerificationCodeInput);
        syncEmailVerificationOtpDisplay();
      }

      setEmailVerificationTimingFromResponse(result);
      setEmailVerificationMessage("sent", "Check your inbox to continue.");
      syncEmailVerificationPanel();

      if (emailVerificationCodeInput) {
        try { emailVerificationCodeInput.focus(); } catch (e) {}
      }

      return true;
    } catch (error) {
      setEmailVerificationMessage("invalid", "Unable to send the verification code right now.");
      return false;
    } finally {
      setEmailVerificationBusy(false);
    }
  }

  async function verifyEmailVerificationCode() {
    if (!emailInput || !emailVerificationCodeInput) return false;

    const email = emailInput.value.trim();
    const code = emailVerificationCodeInput.value.trim();

    if (!/^\d{6}$/.test(code)) {
      markInvalid(emailVerificationCodeInput, "Enter the 6-digit verification code.");
      setEmailVerificationMessage("invalid", "Enter the 6-digit verification code.");
      try { emailVerificationCodeInput.focus(); } catch (e) {}
      return false;
    }

    setEmailVerificationBusy(true, "verify");
    setEmailVerificationMessage("verifying", "Checking verification code...");

    try {
      const response = await fetch(getEmailVerificationVerifyUrl(), {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Accept": "application/json",
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
          "X-CSRF-TOKEN": getCsrfToken(),
        },
        body: JSON.stringify({ email, code }),
      });

      const result = await response.json().catch(() => ({}));

      if (!response.ok) {
        const message = result?.errors
          ? Object.values(result.errors).flat()[0]
          : (result?.message || "The verification code could not be confirmed.");

        markInvalid(emailVerificationCodeInput, message);
        setEmailVerificationMessage("invalid", message);
        return false;
      }

      clearInvalid(emailInput);
      clearInvalid(emailVerificationCodeInput);
      emailVerificationState.verifiedEmail = getNormalizedAccountEmail();
      emailVerificationState.expiresAt = null;
      emailVerificationState.resendAvailableAt = null;
      if (form) {
        form.dataset.emailVerified = "true";
      }

      setEmailVerificationMessage("verified", result?.message || "Email verified. Set your password below.");
      syncEmailVerificationPanel();
      syncAccountPasswordGate();
      try { emailVerificationTitle?.focus(); } catch (e) {}
      return true;
    } catch (error) {
      setEmailVerificationMessage("invalid", "Unable to verify the code right now.");
      return false;
    } finally {
      setEmailVerificationBusy(false);
    }
  }

  async function handleAccountStepEnterAction() {
    if (!sectionContainsElement(currentStep, emailInput) || isCurrentEmailVerified()) {
      return false;
    }

    const email = getNormalizedAccountEmail();
    if (!email) {
      markInvalid(emailInput, "Enter your email address before requesting a code.");
      setEmailVerificationMessage("invalid", "Enter your email address before requesting a code.");
      try { emailInput?.focus(); } catch (e) {}
      return true;
    }

    syncEmailVerificationPanel();

    if (!hasEmailVerificationCodeForCurrentEmail()) {
      await sendEmailVerificationCode();
      return true;
    }

    const code = (emailVerificationCodeInput?.value || "").trim();
    if (/^\d{6}$/.test(code)) {
      await verifyEmailVerificationCode();
      return true;
    }

    markInvalid(emailVerificationCodeInput, "Enter the 6-digit verification code.");
    setEmailVerificationMessage("sent", "Enter the email code, then press Enter or click Verify.");
    try { emailVerificationCodeInput?.focus(); } catch (e) {}

    return true;
  }

  function setVerificationContinueEnabled(enabled) {
    if (!verificationNextButton) return;

    verificationNextButton.disabled = !enabled;
    verificationNextButton.classList.toggle("opacity-60", !enabled);
    verificationNextButton.classList.toggle("cursor-not-allowed", !enabled);
    verificationNextButton.setAttribute("aria-disabled", enabled ? "false" : "true");
  }

  function getVerificationValues() {
    const values = {};

    verificationFieldNames.forEach((fieldName) => {
      const input = document.querySelector(`[name="${fieldName}"]`);
      values[fieldName] = input ? (input.value || "").trim() : "";
    });

    return values;
  }

  function getVerificationSignature(values = getVerificationValues()) {
    return verificationFieldNames
      .map((fieldName) => (values[fieldName] || "").trim().toLowerCase())
      .join("|");
  }

  function isVerificationReady(values = getVerificationValues()) {
    return Boolean(
      values.firstname
      && values.lastname
      && values.birthdate
      && values.collegeGraduated
      && values.yearGraduated
      && values.yearGraduated !== "N/A"
    );
  }

  function abortVerificationRequest() {
    if (!verificationRequestController) return;

    verificationRequestController.abort();
    verificationRequestController = null;
  }

  function syncVerificationIdleMessage() {
    const values = getVerificationValues();

    if (!isVerificationReady(values)) {
      setVerificationStatus(
        "idle",
        "Enter your first name, middle name if available, last name, birthdate, college, and graduation year to check the CMU records live."
      );
      return;
    }

    if (verificationState.status !== "valid") {
      setVerificationStatus("idle", "Details complete. Checking will run automatically.");
    }
  }

  function setVerificationStatus(status, message = "") {
    verificationState.status = status;
    setVerificationContinueEnabled(isVerificationReady());

    if (!verificationStatusMessage) return;

    verificationStatusMessage.textContent = message;
    verificationStatusMessage.classList.remove("text-gray-500", "text-green-700", "text-red-600");

    if (status === "valid") {
      verificationStatusMessage.classList.add("text-green-700");
      return;
    }

    if (status === "invalid") {
      verificationStatusMessage.classList.add("text-red-600");
      return;
    }

    verificationStatusMessage.classList.add("text-gray-500");
  }

  function resetVerificationState() {
    if (verificationCheckTimeoutId) {
      clearTimeout(verificationCheckTimeoutId);
      verificationCheckTimeoutId = null;
    }

    abortVerificationRequest();
    clearVerifiedIdentityFieldLocks();

    if (form) {
      form.dataset.alumniVerified = "false";
    }

    verificationState.signature = "";
    verificationState.verifiedCollege = "";
    verificationState.verifiedDegree = "";
    verificationState.verifiedYear = "";
    verificationState.verifiedProgramLevel = "";
    syncVerificationIdleMessage();
    syncVerificationProgramLink();
    validateUndergraduateCollegeMatch();
  }

  function isValidEmailFormat(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  async function checkExistingAlumniAccount(values = getVerificationValues()) {
    const params = new URLSearchParams({
      firstname: values.firstname || "",
      lastname: values.lastname || "",
      birthdate: values.birthdate || "",
      studentIdnum: values.studentIdnum || "",
      yearGraduated: values.yearGraduated || "",
    });

    try {
      const response = await fetch(`/signup/check-existing-alumni?${params.toString()}`, {
        method: "GET",
        credentials: "same-origin",
        headers: {
          "Accept": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) {
        return "";
      }

      const result = await response.json().catch(() => ({}));

      return result?.exists ? (result?.message || "") : "";
    } catch (error) {
      return "";
    }
  }

  async function checkEmailAvailability(email) {
    const normalizedEmail = (email || "").trim();
    emailCheckState.email = normalizedEmail;

    if (!normalizedEmail) {
      setEmailStatus("invalid", "Email is required.");
      resetEmailVerificationState();
      syncEmailVerificationPanel();
      return false;
    }

    if (!isValidEmailFormat(normalizedEmail)) {
      setEmailStatus("invalid", "Please enter a valid email format.");
      resetEmailVerificationState();
      syncEmailVerificationPanel();
      return false;
    }

    setEmailStatus("checking", "Checking email availability...");

    try {
      const response = await fetch(`/signup/check-email?email=${encodeURIComponent(normalizedEmail)}`, {
        method: "GET",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) {
        setEmailStatus("invalid", "Unable to validate email right now.");
        syncEmailVerificationPanel();
        return false;
      }

      const result = await response.json();
      const stillSameEmail = emailInput && emailInput.value.trim() === normalizedEmail;
      if (!stillSameEmail) return false;

      if (result.available && result.valid_format) {
        setEmailStatus("valid", result.message || "Email is available.");
        syncEmailVerificationPanel();
        return true;
      }

      setEmailStatus("invalid", result.message || "This email is not available.");
      resetEmailVerificationState();
      syncEmailVerificationPanel();
      return false;
    } catch (error) {
      setEmailStatus("invalid", "Unable to validate email right now.");
      syncEmailVerificationPanel();
      return false;
    }
  }

  async function verifyAlumniRecord(options = {}) {
    if (!form) return false;

    const { silent = false } = options;
    if (verificationCheckTimeoutId) {
      clearTimeout(verificationCheckTimeoutId);
      verificationCheckTimeoutId = null;
    }

    const values = getVerificationValues();
    const signature = getVerificationSignature(values);
    const yearGraduatedInput = document.getElementById("yearGraduated");

    if (!isVerificationReady(values)) {
      if (!silent) {
        syncVerificationIdleMessage();
      }
      return false;
    }

    if (yearGraduatedInput && values.yearGraduated === "N/A") {
      markInvalid(yearGraduatedInput, "Please select a valid graduation year.");
      setVerificationStatus("invalid", "Please select a valid graduation year before checking the CMU records.");
      return false;
    }

    const middleNameInput = document.querySelector('[name="middlename"]');
    const middleNameMessage = getMiddleNameValidationMessage(values.middlename);
    if (middleNameMessage) {
      if (middleNameInput) {
        markInvalid(middleNameInput, middleNameMessage);
      }
      setVerificationStatus("invalid", middleNameMessage);
      return false;
    }

    if (middleNameInput) {
      clearInvalid(middleNameInput);
    }

    const existingAlumniAccountMessage = await checkExistingAlumniAccount(values);
    if (existingAlumniAccountMessage) {
      if (signature !== getVerificationSignature()) return false;

      setVerificationStatus("invalid", existingAlumniAccountMessage);
      return false;
    }

    if (
      verificationState.status === "valid"
      && verificationState.signature === signature
      && form.dataset.alumniVerified === "true"
    ) {
      return true;
    }

    const payload = new FormData();
    const csrfTokenInput = form.querySelector('input[name="_token"]');
    if (csrfTokenInput) {
      payload.append("_token", csrfTokenInput.value);
    }

    verificationFieldNames.forEach((fieldName) => {
      payload.append(fieldName, values[fieldName] || "");
    });

    abortVerificationRequest();
    verificationRequestController = new AbortController();
    const requestToken = ++verificationRequestToken;

    setVerificationStatus("checking", "Checking the CMU alumni records...");

    try {
      const response = await fetch("/signup/verify-alumni", {
        method: "POST",
        body: payload,
        credentials: "same-origin",
        signal: verificationRequestController.signal,
        headers: {
          "Accept": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      const result = await response.json().catch(() => ({}));

      if (!response.ok) {
        const message = result?.errors
          ? Object.values(result.errors).flat()[0]
          : (result?.message || "Unable to verify your alumni record right now.");

        if (requestToken !== verificationRequestToken) return false;

        setVerificationStatus("invalid", message);
        return false;
      }

      if (requestToken !== verificationRequestToken) return false;

      if (form) {
        form.dataset.alumniVerified = "true";
      }
      verificationState.signature = signature;

      const verifiedFirstname = result?.verification?.firstname || values.firstname || "";
      const verifiedMiddlename = result?.verification?.middlename || values.middlename || "";
      const verifiedLastname = result?.verification?.lastname || values.lastname || "";
      const verifiedCollege = result?.verification?.collegeGraduated || "";
      const verifiedDegree = result?.verification?.degreeGraduated || values.degreeGraduated || "";
      const verifiedYear = result?.verification?.yearGraduated || values.yearGraduated || "";
      syncVerifiedIdentityFields({
        firstname: verifiedFirstname,
        middlename: verifiedMiddlename,
        lastname: verifiedLastname,
        collegeGraduated: verifiedCollege,
        yearGraduated: verifiedYear,
      });
      verificationState.verifiedCollege = verifiedCollege;
      verificationState.verifiedDegree = verifiedDegree;
      verificationState.verifiedYear = verifiedYear;
      verificationState.verifiedProgramLevel = getVerificationProgramLevel(verifiedDegree, verifiedCollege);
      const departmentSelect = document.getElementById("department");
      if (departmentSelect && verifiedCollege) {
        departmentSelect.value = verifiedCollege;
        departmentSelect.dispatchEvent(new Event("change"));
      }

      setVerificationStatus("valid", result?.message || "CMU alumni record verified.");
      syncVerificationProgramLink({
        college: verifiedCollege,
        degree: verifiedDegree,
        year: verifiedYear,
        preferVerifiedState: true,
      });
      validateUndergraduateCollegeMatch();
      return true;
    } catch (error) {
      if (error.name === "AbortError") {
        return false;
      }

      setVerificationStatus("invalid", "Unable to verify your alumni record right now.");
      return false;
    } finally {
      if (requestToken === verificationRequestToken) {
        verificationRequestController = null;
      }
    }
  }

  function queueLiveVerification(delay = 350) {
    if (!form) return;

    if (verificationCheckTimeoutId) {
      clearTimeout(verificationCheckTimeoutId);
      verificationCheckTimeoutId = null;
    }

    const values = getVerificationValues();
    const signature = getVerificationSignature(values);
    if (!isVerificationReady(values)) {
      syncVerificationIdleMessage();
      return;
    }

    if (
      verificationState.status === "valid"
      && verificationState.signature === signature
      && form.dataset.alumniVerified === "true"
    ) {
      setVerificationContinueEnabled(true);
      return;
    }

    setVerificationStatus("checking", "Checking the CMU alumni records...");
    verificationCheckTimeoutId = setTimeout(() => {
      verifyAlumniRecord({ silent: true });
    }, delay);
  }

  // ==================== HISTORY STATE FOR FORM STEPS ====================
  function pushStepToHistory(step) {
    window.history.pushState({ step: step }, null, "");
  }

  function goToStep(step, options = {}) {
    const { pushHistory = false } = options;
    currentStep = normalizeStep(step);
    showStep(currentStep);
    window.scrollTo(0, 0);

    if (pushHistory) {
      pushStepToHistory(currentStep);
    }
  }

  async function completeCurrentSectionForNavigation() {
    const result = validateCurrentSection(currentStep);
    if (!result.ok) {
      showStep(currentStep);
      if (result.firstInvalid) {
        try { result.firstInvalid.focus(); } catch (e) {}
      }
      return false;
    }

    const miniViewResult = ensureSavedMiniViewsForCurrentStep(currentStep);
    if (!miniViewResult.ok) {
      setMiniViewValidationMessage(miniViewResult.container, miniViewResult.message);
      return false;
    }

    if (sectionContainsElement(currentStep, emailInput)) {
      const email = emailInput.value.trim();
      const emailOk = await checkEmailAvailability(email);
      if (!emailOk) {
        markInvalid(emailInput);
        try { emailInput.focus(); } catch (e) {}
        return false;
      }

      if (!isCurrentEmailVerified()) {
        syncEmailVerificationPanel();
        markInvalid(emailInput, "Please verify your email address before continuing.");
        setEmailVerificationMessage("invalid", emailVerificationState.sentEmail === getNormalizedAccountEmail()
          ? "Enter the email code, then click Verify."
          : "Send and verify a code before continuing.");

        const focusTarget = emailVerificationState.sentEmail === getNormalizedAccountEmail()
          ? emailVerificationCodeInput
          : sendEmailVerificationBtn;
        if (focusTarget) {
          try { focusTarget.focus(); } catch (e) {}
        }
        return false;
      }

      clearInvalid(emailInput);
    }

    if (currentStep === 0) {
      if (verificationState.status !== "valid") {
        syncVerificationIdleMessage();
      }

      const alumniVerified = await verifyAlumniRecord();
      if (!alumniVerified) {
        const firstVerificationField = verificationFieldNames
          .map((fieldName) => document.querySelector(`[name="${fieldName}"]`))
          .find((input) => !!input);
        if (firstVerificationField) {
          try { firstVerificationField.focus(); } catch (e) {}
        }
        return false;
      }
    }

    highestUnlockedStep = Math.max(highestUnlockedStep, normalizeStep(currentStep + 1));
    updateStepChips(currentStep);
    return true;
  }

  async function navigateWithStepChip(targetStep) {
    const normalizedTarget = normalizeStep(targetStep);
    if (normalizedTarget === currentStep || stepNavigationInProgress) return;
    if (normalizedTarget > highestUnlockedStep) return;

    stepNavigationInProgress = true;

    try {
      if (normalizedTarget > currentStep) {
        const completedCurrent = await completeCurrentSectionForNavigation();
        if (!completedCurrent) return;
      }

      goToStep(normalizedTarget, { pushHistory: true });
    } finally {
      stepNavigationInProgress = false;
    }
  }

  window.history.replaceState({ step: currentStep }, null, "");

  window.addEventListener("popstate", function (event) {
    if (event.state && typeof event.state.step !== "undefined") {
      currentStep = Math.min(normalizeStep(event.state.step), highestUnlockedStep);
      goToStep(currentStep);
    } else {
      goToStep(0);
      window.history.replaceState({ step: currentStep }, null, "");
    }
  });

  // ==================== NEXT / PREVIOUS BUTTONS ====================
  document.querySelectorAll(".next-btn").forEach((btn) => {
    btn.addEventListener("click", async () => {
      if (stepNavigationInProgress) return;

      stepNavigationInProgress = true;

      try {
        const completedCurrent = await completeCurrentSectionForNavigation();
        if (!completedCurrent) return;

        if (currentStep < totalSections - 1) {
          goToStep(currentStep + 1, { pushHistory: true });
        }
      } finally {
        stepNavigationInProgress = false;
      }
    });
  });

  document.querySelectorAll(".previous-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      if (currentStep > 0) {
        goToStep(currentStep - 1);
      }
    });
  });

  const stepChipsEl = document.getElementById("stepChips");
  if (stepChipsEl) {
    stepChipsEl.addEventListener("click", function (event) {
      const chip = event.target instanceof HTMLElement
        ? event.target.closest(".registration-step-chip")
        : null;

      if (!(chip instanceof HTMLButtonElement) || chip.disabled) return;

      navigateWithStepChip(chip.dataset.stepIndex);
    });
  }

  // ==================== HELPER FUNCTIONS ====================
  function populateDropdown(selectId, options, placeholder = "Select") {
    const select = document.getElementById(selectId);
    if (!select) return;

    select.innerHTML = "";
    select.add(new Option(placeholder, ""));
    options.forEach((opt) => select.add(new Option(opt, opt)));
  }

  function populateYearDropdown(selectElementId, startYear = 1914) {
    const yearSelect = document.getElementById(selectElementId);
    if (!yearSelect) return;

    const currentYear = new Date().getFullYear();
    yearSelect.innerHTML = "";
    yearSelect.add(new Option("Select Year", ""));
    yearSelect.add(new Option("N/A", "N/A"));

    for (let year = currentYear; year >= startYear; year--) {
      yearSelect.add(new Option(year, year));
    }
  }

  // ==================== DROPDOWN POPULATION ====================
  const collegeOptions = [
    "College of Agriculture",
    "College of Arts and Sciences",
    "College of Business and Management",
    "College of Education",
    "College of Engineering",
    "College of Forestry and Environmental Science",
    "College of Human Ecology",
    "College of Information Sciences and Computing",
    "College of Nursing",
    "College of Veterinary Medicine",
    "Other",
  ];

  const undergraduateProgramsByCollege = {
    "College of Agriculture": [
      "Bachelor of Science in Agriculture",
      "Bachelor of Science in Agribusiness Management",
      "Bachelor of Science in Development Communication",
    ],
    "College of Arts and Sciences": [
      "Bachelor of Arts in English",
      "Bachelor of Arts in English Language Studies",
      "Bachelor of Arts in History",
      "Bachelor of Arts in Political Science",
      "Bachelor of Arts in Psychology",
      "Bachelor of Arts in Sociology",
      "Bachelor of Science in Biology",
      "Bachelor of Science in Chemistry",
      "Bachelor of Science in Mathematics",
      "Bachelor of Science in Mathematics minor in Statistics",
      "Bachelor of Science in Physics",
      "Bachelor of Science in Psychology",
    ],
    "College of Business and Management": [
      "Bachelor of Science in Accountancy",
      "Bachelor of Science in Accounting Information System",
      "Bachelor of Science in Business Administration",
      "Bachelor of Science in Internal Auditing",
      "Bachelor of Science in Management Accounting",
      "Bachelor of Science in Office Administration",
    ],
    "College of Education": [
      "Bachelor of Secondary Education",
      "Bachelor of Physical Education",
      "Certificate in Teaching",
    ],
    "College of Engineering": [
      "Bachelor of Science in Agricultural Engineering",
      "Bachelor of Science in Agricultural & Biosystems Engineering",
      "Bachelor of Science in Civil Engineering",
      "Bachelor of Science in Electrical Engineering",
      "Bachelor of Science in Mechanical Engineering",
    ],
    "College of Forestry and Environmental Science": [
      "Bachelor of Science in Environmental Science",
      "Bachelor of Science in Forestry",
    ],
    "College of Human Ecology": [
      "Bachelor of Science in Home Economics",
      "Bachelor of Science in Food Technology",
      "Bachelor of Science in Hotel and Restaurant Management",
      "Bachelor of Science in Hospitality Management",
      "Bachelor of Science in Nutrition and Dietetics",
    ],
    "College of Information Sciences and Computing": [
      "Bachelor of Science in Information Technology",
    ],
    "College of Nursing": [
      "Bachelor of Science in Nursing",
    ],
    "College of Veterinary Medicine": [
      "Doctor of Veterinary Medicine",
    ],
    "Other": ["Other"],
  };

  const undergraduateMajorsByDegree = {
    "Bachelor of Science in Agriculture": [
      "Agricultural Economics",
      "Agricultural Extension",
      "Animal Science",
      "Crop Protection (Entomology)",
      "Crop Protection (Plant Pathology)",
      "Crop Science (Agronomy)",
      "Crop Science (Horticulture)",
      "Crop Science (Plant Breeding)",
      "Soil Science",
    ],
    "Bachelor of Science in Agribusiness Management": ["Agribusiness Management"],
    "Bachelor of Science in Development Communication": ["Development Communication"],
    "Bachelor of Arts in English": ["English"],
    "Bachelor of Arts in English Language Studies": ["English Language Studies"],
    "Bachelor of Arts in History": ["History"],
    "Bachelor of Arts in Political Science": ["Political Science"],
    "Bachelor of Arts in Psychology": ["Psychology"],
    "Bachelor of Arts in Sociology": ["Sociology"],
    "Bachelor of Science in Biology": ["Biology"],
    "Bachelor of Science in Chemistry": ["Chemistry"],
    "Bachelor of Science in Mathematics": ["Mathematics"],
    "Bachelor of Science in Mathematics minor in Statistics": ["Mathematics minor in Statistics"],
    "Bachelor of Science in Physics": ["Physics"],
    "Bachelor of Science in Psychology": ["Psychology"],
    "Bachelor of Science in Accountancy": ["Accountancy"],
    "Bachelor of Science in Accounting Information System": ["Accounting Information System"],
    "Bachelor of Science in Business Administration": [
      "Financial Management",
      "Marketing Management",
      "Operations Management",
    ],
    "Bachelor of Science in Internal Auditing": ["Internal Auditing"],
    "Bachelor of Science in Management Accounting": ["Management Accounting"],
    "Bachelor of Science in Office Administration": ["Office Administration"],
    "Bachelor of Secondary Education": [
      "Biology",
      "English",
      "Filipino",
      "General Science",
      "Mathematics",
      "Physical Education",
      "Physics",
      "Sciences",
    ],
    "Bachelor of Physical Education": ["Physical Education"],
    "Certificate in Teaching": ["Teaching"],
    "Bachelor of Science in Agricultural Engineering": ["Agricultural Engineering"],
    "Bachelor of Science in Agricultural & Biosystems Engineering": ["Agricultural & Biosystems Engineering"],
    "Bachelor of Science in Civil Engineering": ["Civil Engineering"],
    "Bachelor of Science in Electrical Engineering": ["Electrical Engineering"],
    "Bachelor of Science in Mechanical Engineering": ["Mechanical Engineering"],
    "Bachelor of Science in Environmental Science": ["Environmental Science"],
    "Bachelor of Science in Forestry": ["Forestry"],
    "Bachelor of Science in Home Economics": [
      "Food Business Management (FSO)",
      "Food Business Management (FPO)",
      "Home Economics Education (Elementary School Teaching Option)",
    ],
    "Bachelor of Science in Food Technology": ["Food Technology"],
    "Bachelor of Science in Hotel and Restaurant Management": ["Hotel and Restaurant Management"],
    "Bachelor of Science in Hospitality Management": ["Hospitality Management"],
    "Bachelor of Science in Nutrition and Dietetics": ["Nutrition and Dietetics"],
    "Bachelor of Science in Information Technology": ["Information Technology"],
    "Bachelor of Science in Nursing": ["Nursing"],
    "Doctor of Veterinary Medicine": ["Veterinary Medicine"],
    "Other": ["Other"],
  };

  const verificationCollegeOptions = [
    ...collegeOptions.filter((option) => option !== "Other"),
    "Non-Degree Programs",
  ];

  function undergraduateSectionIsActive() {
    return document.querySelector('input[name="hasUndergraduateDegree"]:checked')?.value === "Yes";
  }

  function setVerificationChoiceMessage(target, status, message = "") {
    if (!target) return;

    target.textContent = message;
    target.classList.remove("hidden", "text-gray-500", "text-green-700", "text-red-600");

    if (!message) {
      target.classList.add("hidden");
      return;
    }

    if (status === "valid") {
      target.classList.add("text-green-700");
      return;
    }

    if (status === "invalid") {
      target.classList.add("text-red-600");
      return;
    }

    target.classList.add("text-gray-500");
  }

  function setUndergraduateVerificationMessage(status, message = "") {
    setVerificationChoiceMessage(undergraduateVerificationMessage, status, message);
  }

  function setNondegreeVerificationMessage(status, message = "") {
    setVerificationChoiceMessage(nondegreeVerificationMessage, status, message);
  }

  function setUndergraduateCollegeMatchStatus(status, message = "") {
    if (!undergraduateCollegeMatchMessage) return;

    undergraduateCollegeMatchMessage.textContent = message;
    undergraduateCollegeMatchMessage.classList.remove("hidden", "text-gray-500", "text-green-700", "text-red-600");

    if (!message) {
      undergraduateCollegeMatchMessage.classList.add("hidden");
      return;
    }

    if (status === "valid") {
      undergraduateCollegeMatchMessage.classList.add("text-green-700");
      return;
    }

    if (status === "invalid") {
      undergraduateCollegeMatchMessage.classList.add("text-red-600");
      return;
    }

    undergraduateCollegeMatchMessage.classList.add("text-gray-500");
  }

  function normalizeVerifiedCollegeValue(value) {
    return String(value || "")
      .trim()
      .toLowerCase()
      .replace(/\s+/g, " ");
  }

  function getSelectedUndergraduateCollegeValue() {
    if (!undergradDepartmentSelect || undergradDepartmentSelect.disabled) {
      return "";
    }

    const selectedCollege = (undergradDepartmentSelect.value || "").trim();
    if (!selectedCollege) {
      return "";
    }

    if (selectedCollege === "Other") {
      return (departmentOtherInput?.value || "").trim();
    }

    return selectedCollege;
  }

  function validateUndergraduateCollegeMatch(fieldset = null) {
    if (!undergradDepartmentSelect) {
      return { ok: true, firstInvalid: null, message: "" };
    }

    const undergraduateFieldset = undergradDepartmentSelect.closest("fieldset.form-step");
    if (fieldset && undergraduateFieldset && fieldset !== undergraduateFieldset) {
      return { ok: true, firstInvalid: null, message: "" };
    }

    if (!undergraduateSectionIsActive()) {
      setUndergraduateCollegeMatchStatus("idle", "");
      return { ok: true, firstInvalid: null, message: "" };
    }

    const verifiedCollege = (verificationState.verifiedCollege || "").trim();
    if (!verifiedCollege) {
      setUndergraduateCollegeMatchStatus(
        "idle",
        "Your undergraduate college must match the verified CMU record from Step 1."
      );
      return { ok: true, firstInvalid: null, message: "" };
    }

    const selectedCollege = getSelectedUndergraduateCollegeValue();
    if (!selectedCollege) {
      setUndergraduateCollegeMatchStatus(
        "idle",
        `Use the same college as your verified CMU record: ${verifiedCollege}.`
      );
      return { ok: true, firstInvalid: null, message: "" };
    }

    if (normalizeVerifiedCollegeValue(selectedCollege) !== normalizeVerifiedCollegeValue(verifiedCollege)) {
      const mismatchMessage = `Mismatch detected. Your verified CMU college is ${verifiedCollege}. Please select the same college here.`;
      const firstInvalid = undergradDepartmentSelect.value === "Other" && departmentOtherInput
        ? departmentOtherInput
        : undergradDepartmentSelect;

      setUndergraduateCollegeMatchStatus("invalid", mismatchMessage);

      return {
        ok: false,
        firstInvalid,
        message: "",
      };
    }

    setUndergraduateCollegeMatchStatus(
      "valid",
      `Matched with your verified CMU record: ${verifiedCollege}.`
    );

    return { ok: true, firstInvalid: null, message: "" };
  }

  function getUndergraduateProgramsForCollege(college) {
    if (!college) return [];
    return undergraduateProgramsByCollege[college] || [];
  }

  function getUndergraduateMajorsForDegree(degree) {
    if (!degree) return [];
    return undergraduateMajorsByDegree[degree] || ["N/A"];
  }

  populateDropdown("department", collegeOptions, "Select Department");

  function selectHasOption(select, value) {
    if (!select || !value) return false;

    return Array.from(select.options).some((option) => option.value === value);
  }

  function normalizeVerificationProgramValue(value) {
    let normalized = String(value || "").toLowerCase().trim();
    if (!normalized) return "";

    const replacements = [
      [/\bbsit\b/g, "bachelor of science in information technology"],
      [/\bbsed\b/g, "bachelor of secondary education"],
      [/\bbpe\b/g, "bachelor of physical education"],
      [/\bmba\b/g, "master in business administration"],
      [/\bph\.?d\.?\b/g, "doctor of philosophy"],
      [/\bdvm\b/g, "doctor of veterinary medicine"],
      [/\bbs\b/g, "bachelor of science"],
      [/\bba\b/g, "bachelor of arts"],
      [/\bms\b/g, "master of science"],
      [/\bma\b/g, "master of arts"],
    ];

    replacements.forEach(([pattern, replacement]) => {
      normalized = normalized.replace(pattern, replacement);
    });

    return normalized
      .replace(/&/g, " and ")
      .replace(/[^a-z0-9]+/g, " ")
      .replace(/\b(of|in|and)\b/g, " ")
      .replace(/\s+/g, " ")
      .trim();
  }

  function getVerificationProgramLevel(program = "", college = "") {
    const normalizedProgram = normalizeVerificationProgramValue(program);
    if (!normalizedProgram) return "";

    if (college && verificationProgramsByCollege[college]) {
      const programGroups = verificationProgramsByCollege[college];

      if ((programGroups.undergraduate || []).some((entry) => normalizeVerificationProgramValue(entry) === normalizedProgram)) {
        return "undergraduate";
      }

      if ((programGroups.master || []).some((entry) => normalizeVerificationProgramValue(entry) === normalizedProgram)) {
        return "master";
      }

      if ((programGroups.doctorate || []).some((entry) => normalizeVerificationProgramValue(entry) === normalizedProgram)) {
        return "doctorate";
      }

      if ((programGroups.nondegree || []).some((entry) => normalizeVerificationProgramValue(entry) === normalizedProgram)) {
        return "nondegree";
      }
    }

    if (verificationUndergraduateProgramsSet.has(normalizedProgram)) return "undergraduate";
    if (verificationMasterProgramsSet.has(normalizedProgram)) return "master";
    if (verificationDoctorateProgramsSet.has(normalizedProgram)) return "doctorate";
    if (verificationNonDegreeProgramsSet.has(normalizedProgram)) return "nondegree";

    return "";
  }

  function syncVerificationDegreeOptions(preferredValue = "") {
    if (!verificationDegreeSelect) return;

    const selectedCollege = document.getElementById("collegeGraduated")?.value || "";
    verificationDegreeSelect.innerHTML = "";

    if (!selectedCollege) {
      verificationDegreeSelect.add(new Option("Select college first", ""));
      verificationDegreeSelect.value = "";
      verificationDegreeSelect.disabled = true;
      return;
    }

    verificationDegreeSelect.add(new Option("Select Degree / Program", ""));

    const programGroups = verificationProgramsByCollege[selectedCollege] || {};
    [
      ["Undergraduate Programs", programGroups.undergraduate],
      ["Master's Programs", programGroups.master],
      ["Doctorate Programs", programGroups.doctorate],
      ["Non-Degree Programs", programGroups.nondegree],
    ].forEach(([label, programs]) => {
      if (!Array.isArray(programs) || programs.length === 0) {
        return;
      }

      const group = document.createElement("optgroup");
      group.label = label;

      programs.forEach((program) => {
        group.appendChild(new Option(program, program));
      });

      verificationDegreeSelect.appendChild(group);
    });

    verificationDegreeSelect.disabled = false;

    if (preferredValue && selectHasOption(verificationDegreeSelect, preferredValue)) {
      verificationDegreeSelect.value = preferredValue;
      return;
    }

    verificationDegreeSelect.value = "";
  }

  function clearVerificationLinkedValue(select) {
    if (!select || select.dataset.verificationLinked !== "true") return;

    select.value = "";
    delete select.dataset.verificationLinked;
  }

  function setVerificationLinkedValue(select, value) {
    if (!select) return;

    const normalizedValue = String(value || "").trim();
    const matchedValue = getEquivalentSelectValue(select, normalizedValue);
    if (!normalizedValue || normalizedValue === "N/A" || !matchedValue) {
      clearVerificationLinkedValue(select);
      return;
    }

    select.value = matchedValue;
    select.dataset.verificationLinked = "true";
  }

  function clearVerificationProgramLocks() {
    [
      undergradDepartmentSelect,
      undergradDegreeSelect,
      undergradYearSelect,
      masterSelect,
      mastYearSelect,
      doctorateSelect,
      doctYearSelect,
      nondegreeSelect,
      nondegreeYearSelect,
    ].forEach((field) => clearRegistrationFieldLock(field));

    syncLockedRadioChoice("hasUndergraduateDegree");
    syncLockedRadioChoice("hasMasteral");
    syncLockedRadioChoice("hasDoctorate");
    syncLockedRadioChoice("hasNonDegree");
  }

  function syncVerificationGraduationYear(programLevel, year) {
    const verificationYearTargets = {
      undergraduate: undergradYearSelect,
      master: mastYearSelect,
      doctorate: doctYearSelect,
      nondegree: nondegreeYearSelect,
    };

    Object.entries(verificationYearTargets).forEach(([level, select]) => {
      if (!select) return;

      if (level === programLevel) {
        setVerificationLinkedValue(select, year);
        return;
      }

      clearVerificationLinkedValue(select);
    });
  }

  function syncVerificationProgramLink({ college = "", degree = "", year = "", preferVerifiedState = false } = {}) {
    const selectedCollege = college || document.getElementById("collegeGraduated")?.value || verificationState.verifiedCollege || "";
    const selectedDegree = degree
      || (preferVerifiedState ? verificationState.verifiedDegree : "")
      || verificationDegreeSelect?.value
      || "";
    const selectedYear = String(
      year
      || (preferVerifiedState ? verificationState.verifiedYear : "")
      || document.getElementById("yearGraduated")?.value
      || ""
    ).trim();
    const programLevel = getVerificationProgramLevel(selectedDegree, selectedCollege);

    clearVerificationProgramLocks();
    setNondegreeVerificationMessage("neutral", "");

    if (!selectedDegree || !programLevel) {
      syncVerificationGraduationYear("", "");
      setUndergraduateVerificationMessage("neutral", "");
      return;
    }

    const programLevelLabels = {
      master: "a master's",
      doctorate: "a doctorate",
      nondegree: "a non-degree",
      undergraduate: "an undergraduate",
    };

    if (programLevel === "undergraduate") {
      setRadioGroupValue("hasUndergraduateDegree", "Yes");
      syncLockedRadioChoice("hasUndergraduateDegree", "Yes");
      setRadioGroupValue("hasNonDegree", "No");
      syncLockedRadioChoice("hasNonDegree", "No");

      if (undergradDepartmentSelect && selectHasOption(undergradDepartmentSelect, selectedCollege)) {
        undergradDepartmentSelect.value = selectedCollege;
      }

      syncUndergraduateSection();
      syncNondegreeSection();

      const matchedUndergraduateDegree = getEquivalentSelectValue(undergradDegreeSelect, selectedDegree);
      if (undergradDegreeSelect && matchedUndergraduateDegree) {
        undergradDegreeSelect.value = matchedUndergraduateDegree;
        updateUndergraduateMajorOptions();
      }

      syncVerificationGraduationYear("undergraduate", selectedYear);
      setRegistrationFieldLocked(undergradDepartmentSelect, selectedCollege);
      setRegistrationFieldLocked(
        undergradDegreeSelect,
        undergradDegreeSelect?.value || matchedUndergraduateDegree || selectedDegree
      );
      setRegistrationFieldLocked(undergradYearSelect, undergradYearSelect?.value || selectedYear);
      setUndergraduateVerificationMessage(
        "valid",
        `Locked to Yes based on your verified CMU undergraduate record: ${selectedDegree}.`
      );
      setNondegreeVerificationMessage(
        "neutral",
        `Locked to No because ${selectedDegree} is an undergraduate CMU program.`
      );
      return;
    }

    setRadioGroupValue("hasUndergraduateDegree", "No");
    syncLockedRadioChoice("hasUndergraduateDegree", "No");
    syncUndergraduateSection();
    syncVerificationGraduationYear(programLevel, selectedYear);

    if (programLevel === "master") {
      setRadioGroupValue("hasMasteral", "Yes");
      syncLockedRadioChoice("hasMasteral", "Yes");
      syncMasterSection();

      const matchedMasterDegree = getEquivalentSelectValue(masterSelect, selectedDegree);
      if (masterSelect && matchedMasterDegree) {
        masterSelect.value = matchedMasterDegree;
        updateMasterMajorOptions();
      }

      setRegistrationFieldLocked(masterSelect, masterSelect?.value || matchedMasterDegree || selectedDegree);
      setRegistrationFieldLocked(mastYearSelect, mastYearSelect?.value || selectedYear);
    }

    if (programLevel === "doctorate") {
      setRadioGroupValue("hasDoctorate", "Yes");
      syncLockedRadioChoice("hasDoctorate", "Yes");
      syncDoctorateSection();

      const matchedDoctorateDegree = getEquivalentSelectValue(doctorateSelect, selectedDegree);
      if (doctorateSelect && matchedDoctorateDegree) {
        doctorateSelect.value = matchedDoctorateDegree;
        updateDoctorateMajorOptions();
      }

      setRegistrationFieldLocked(doctorateSelect, doctorateSelect?.value || matchedDoctorateDegree || selectedDegree);
      setRegistrationFieldLocked(doctYearSelect, doctYearSelect?.value || selectedYear);
    }

    if (programLevel === "nondegree") {
      setRadioGroupValue("hasNonDegree", "Yes");
      syncLockedRadioChoice("hasNonDegree", "Yes");
      syncNondegreeSection();

      const matchedNondegreeProgram = getEquivalentSelectValue(nondegreeSelect, selectedDegree);
      if (nondegreeSelect && matchedNondegreeProgram) {
        nondegreeSelect.value = matchedNondegreeProgram;
      }

      setRegistrationFieldLocked(nondegreeSelect, nondegreeSelect?.value || matchedNondegreeProgram || selectedDegree);
      setRegistrationFieldLocked(nondegreeYearSelect, nondegreeYearSelect?.value || selectedYear);
      setNondegreeVerificationMessage(
        "valid",
        `Locked to Yes based on your verified CMU non-degree record: ${selectedDegree}.`
      );
    }

    setUndergraduateVerificationMessage(
      "neutral",
      `Locked to No because ${selectedDegree} is ${programLevelLabels[programLevel] || "a non-undergraduate"} CMU program.`
    );
  }

  populateDropdown("collegeGraduated", verificationCollegeOptions, "Select College");

  populateDropdown(
    "regionOfOrigin",
    [
      "NCR – National Capital Region",
      "CAR – Cordillera Administrative Region",
      "Region I – Ilocos Region",
      "Region II – Cagayan Valley",
      "Region III – Central Luzon",
      "Region IV-A – CALABARZON",
      "Region IV-B – MIMAROPA",
      "Region V – Bicol Region",
      "Region VI – Western Visayas",
      "Region VII – Central Visayas",
      "Region VIII – Eastern Visayas",
      "Region IX – Zamboanga Peninsula",
      "Region X – Northern Mindanao",
      "Region XI – Davao Region",
      "Region XII – SOCCSKSARGEN",
      "Region XIII – Caraga",
      "BARMM – Bangsamoro Autonomous Region in Muslim Mindanao",
      "N/A",
    ],
    "Select Region"
  );

  const regionXMunicipalityOptions = [
    "Baungon",
    "Cabanglasan",
    "Damulog",
    "Dangcagan",
    "Don Carlos",
    "Impasug-ong",
    "Kadingilan",
    "Kalilangan",
    "Kibawe",
    "Kitaotao",
    "Lantapan",
    "Libona",
    "Malaybalay City",
    "Malitbog",
    "Manolo Fortich",
    "Maramag",
    "Pangantucan",
    "Quezon",
    "San Fernando",
    "Sumilao",
    "Talakag",
    "Valencia City",
    "Catarman",
    "Guinsiliban",
    "Mahinog",
    "Mambajao",
    "Sagay",
    "Bacolod",
    "Baloi",
    "Baroy",
    "Iligan City",
    "Kapatagan",
    "Sultan Naga Dimaporo",
    "Kauswagan",
    "Kolambugan",
    "Lala",
    "Linamon",
    "Magsaysay",
    "Maigo",
    "Matungao",
    "Munai",
    "Nunungan",
    "Pantao Ragat",
    "Pantar",
    "Poona Piagapo",
    "Salvador",
    "Sapad",
    "Tagoloan",
    "Tangcal",
    "Tubod",
    "Aloran",
    "Baliangao",
    "Bonifacio",
    "Calamba",
    "Clarin",
    "Concepcion",
    "Don Victoriano Chiongbian",
    "Jimenez",
    "Lopez Jaena",
    "Oroquieta City",
    "Ozamiz City",
    "Panaon",
    "Plaridel",
    "Sapang Dalaga",
    "Sinacaban",
    "Tangub City",
    "Tudela",
    "Alubijid",
    "Balingasag",
    "Balingoan",
    "Binuangan",
    "Cagayan de Oro City",
    "Claveria",
    "El Salvador City",
    "Gingoog City",
    "Gitagum",
    "Initao",
    "Jasaan",
    "Kinoguitan",
    "Lagonglong",
    "Laguindingan",
    "Libertad",
    "Lugait",
    "Manticao",
    "Medina",
    "Naawan",
    "Opol",
    "Salay",
    "Sugbongcogon",
    "Talisayan",
    "Villanueva",
  ];
  const regionXProvinceTokens = new Set([
    "bukidnon",
    "camiguin",
    "lanao del norte",
    "misamis occidental",
    "misamis oriental",
  ]);

  populateDropdown("country", ["Philippines", "Other"]);
  populateDropdown("highschool", [
    "CMULHS - Junior Highschool Graduated",
    "CMULHS - Senior Highschool Graduated",
    "CMU Laboratory High School",
    "CMU - Senior High School",
    "N/A",
  ]);

  // ==================== CMU HIGHSCHOOL CONDITIONAL QUESTION ====================
  (function setupHighschoolAlumniQuestion() {
    const highschoolSelect = document.getElementById('highschool');
    if (!highschoolSelect) return;

    // create container right before the existing highschool select
    const container = document.createElement('div');
    container.classList.add('mb-2', 'space-y-3');

    const qLabel = document.createElement('label');
    qLabel.innerText = 'Are you a CMU Laboratory High School alumnus/alumna?';

    const row = document.createElement('div');
    row.classList.add('registration-radio-group');

    const yesWrapper = document.createElement('label');
    yesWrapper.htmlFor = 'isCMULHSYes';
    yesWrapper.classList.add('registration-radio-option');
    const yes = document.createElement('input');
    yes.type = 'radio';
    yes.name = 'isCMULHS';
    yes.id = 'isCMULHSYes';
    yes.value = 'Yes';
    yes.required = true;
    const yesLabel = document.createElement('span');
    yesLabel.innerText = 'Yes';
    yesWrapper.append(yes, yesLabel);

    const noWrapper = document.createElement('label');
    noWrapper.htmlFor = 'isCMULHSNo';
    noWrapper.classList.add('registration-radio-option');
    const no = document.createElement('input');
    no.type = 'radio';
    no.name = 'isCMULHS';
    no.id = 'isCMULHSNo';
    no.value = 'No';
    no.required = true;
    const noLabel = document.createElement('span');
    noLabel.innerText = 'No';
    noWrapper.append(no, noLabel);

    row.append(yesWrapper, noWrapper);

    // Create CMULHS-specific dropdown (will reuse highschoolSelect but limit options)
    const cmuSelect = document.createElement('select');
    cmuSelect.id = 'cmuHighschoolSelect';
    cmuSelect.name = 'cmuHighschoolSelect';
    cmuSelect.classList.add('hidden', 'mt-1');

    ['Select Program', 'CMULHS - Senior Highschool Graduated', 'CMULHS - Junior Highschool Graduated', 'CMU Laboratory High School', 'CMU - Senior High School'].forEach(opt => {
      cmuSelect.add(new Option(opt === 'Select Program' ? opt : opt, opt === 'Select Program' ? '' : opt));
    });

    // Year select already exists with id `yearGraduatedHS` — hide until Yes
    const yearSelect = document.getElementById('yearGraduatedHS');
    const yearLabel = document.querySelector('label[for="yearGraduatedHS"]');
    if (yearSelect) yearSelect.classList.add('hidden');
    if (yearLabel) yearLabel.classList.add('hidden');

    // Add a label for the highschool dropdown (shown only when Yes)
    const highschoolLabel = document.createElement('label');
    highschoolLabel.innerText = 'Highschool Graduated:';
    highschoolLabel.classList.add('hidden');

    container.append(qLabel, row, highschoolLabel, cmuSelect);

    // insert container before the original highschool select's parent
    const parent = highschoolSelect.parentNode;
    parent.insertBefore(container, highschoolSelect);

    // hide the generic highschoolSelect when CMU-specific is used
    function updateHighschoolVisibility() {
      // Default: hide all selects and year unless Yes
      if (yes.checked) {
        highschoolLabel.classList.remove('hidden');
        cmuSelect.classList.remove('hidden');
        cmuSelect.required = true;
        // ensure generic highschool select is hidden
        highschoolSelect.classList.add('hidden');
        highschoolSelect.required = false;
        if (yearSelect) {
          yearLabel && yearLabel.classList.remove('hidden');
          yearSelect.classList.remove('hidden');
          yearSelect.required = true;
        }
      } else if (no.checked) {
        // No: hide both dropdowns and year
        highschoolLabel.classList.add('hidden');
        cmuSelect.classList.add('hidden');
        cmuSelect.required = false;
        highschoolSelect.classList.add('hidden');
        highschoolSelect.required = false;
        if (yearSelect) {
          yearLabel && yearLabel.classList.add('hidden');
          yearSelect.classList.add('hidden');
          yearSelect.required = false;
          yearSelect.value = '';
        }
      } else {
        // neither selected: hide everything
        highschoolLabel.classList.add('hidden');
        cmuSelect.classList.add('hidden');
        cmuSelect.required = false;
        highschoolSelect.classList.add('hidden');
        if (yearSelect) {
          yearLabel && yearLabel.classList.add('hidden');
          yearSelect.classList.add('hidden');
          yearSelect.required = false;
        }
      }
    }

    [yes, no].forEach(r => r.addEventListener('change', updateHighschoolVisibility));
    // initialize
    updateHighschoolVisibility();
  })();

  const nonDegreeProgramOptions = [
    "Short Course in Agriculture",
    "Short Course in Business Administration",
    "Short Course in Education",
    "Vocational Training",
    "Certificate of Short Course in Dairy Production and Management",
    "Certificate of Short Course in Swine and Production",
    "Certificate in Agricultural Sciences",
    "Associate in Sugarcane Production and Management",
    "Certificate in Accounting Technology",
    "Certificate in Cookery NC II",
    "Certificate in Food Processing NC II",
    "Certificate in Food and Beverage Services NC II",
    "Certificate in Health Care Services NC II",
    "Certificate in Teaching",
    "Other",
    "N/A",
  ];

  populateDropdown("nondegree", nonDegreeProgramOptions, "Select Program");

  populateDropdown("honorORaward", [
    "Cum Laude",
    "Magna Cum Laude",
    "Summa Cum Laude",
    "Dean's Lister",
    "Valedictorian",
    "Salutatorian",
    "Other",
    "N/A",
  ]);

  // ==================== PROFESSIONAL EXAMS ====================
  const professionalExams = [
    "Professional Civil Service Examination",
    "Subprofessional Civil Service Examination",
    "Guidance Counselor Licensure Examination",
    "Agricultural And Biosystems Engineer Licensure Examination",
    "Nurse Licensure Examination",
    "Geologist Licensure Examination",
    "Mining Engineer Licensure Examination",
    "Physician Licensure Examination",
    "Veterinarian Licensure Examination",
    "Nutritionist-Dietitian Licensure Examination",
    "Physical Therapy Licensure Examination",
    "Agriculturist Licensure Examination",
    "Licensure Examination for Professional Teachers",
    "Certified Public Accountant Licensure Examination",
    "Mechanical Engineering Licensure Examination",
    "Electrical Engineering Licensure Examination",
    "Foresters Licensure Examination",
    "Board Licensure Examination for Psychometrician",
    "Chemical Technician Licensure Examination",
    "Chemist Licensure Examination",
    "Other",
    "N/A",
  ];

  const proExamContainer = document.getElementById("proExamContainer");
  const proExamOtherContainer = document.getElementById("proExamOtherContainer");

  if (proExamContainer) {
    professionalExams.forEach((exam) => {
      const wrapper = document.createElement("div");

      // Tailwind layout for checkbox items
      wrapper.classList.add(
        "flex",
        "items-center",
        "justify-between",
        "gap-3",
        "p-2",
        "border",
        "rounded-lg",
        "border-gray-200"
      );

      const label = document.createElement("label");
      label.innerText = exam;
      label.classList.add("text-sm", "text-gray-700");

      const checkbox = document.createElement("input");
      checkbox.type = "checkbox";
      checkbox.name = "proExam[]";
      checkbox.value = exam;
      checkbox.id = exam.replace(/\s+/g, "_");
      checkbox.addEventListener("change", function () {
        if (Array.from(proExamContainer.querySelectorAll('input[type="checkbox"]')).some((input) => input.checked)) {
          proExamContainer.querySelectorAll('input[type="checkbox"]').forEach(clearInvalid);
          clearInlineGroupError(proExamContainer, "proExam[]");
        }
      });

      wrapper.appendChild(label);
      wrapper.appendChild(checkbox);
      proExamContainer.appendChild(wrapper);

      if (exam === "Other") {
        checkbox.addEventListener("change", function () {
          if (!proExamOtherContainer) return;
          const otherInput = document.getElementById("proExamOther");
          proExamOtherContainer.classList.toggle("hidden", !this.checked);
          if (otherInput) {
            otherInput.disabled = !this.checked;
            otherInput.required = this.checked;
            if (!this.checked) otherInput.value = "";
          }
        });
      }
    });
  }

  populateDropdown("proExamreason", [
    "Licensure Requirement",
    "Career Advancement",
    "Personal Interest",
    "Other",
  ]);

  populateDropdown("trainingcount", ["0", "1", "2", "3", "4", "5+"]);

  populateYearDropdown("yearGraduated");
  populateYearDropdown("ugyearGraduated");
  populateYearDropdown("mastyearGraduated");
  populateYearDropdown("doctyearGraduated");
  populateYearDropdown("nondegreeyearGraduated");
  populateYearDropdown("yearGraduatedHS");
  populateYearDropdown("proExamyear");

  const educationBackgroundFields = document.getElementById("educationBackgroundFields");
  const educationBackgroundChoiceInputs = document.querySelectorAll('input[name="hasEducationalBackground"]');
  const proExamYearSelect = document.getElementById("proExamyear");
  const proExamReasonSelect = document.getElementById("proExamreason");
  const trainingCountSelect = document.getElementById("trainingcount");

  function setEducationalBackgroundEnabled(isEnabled) {
    document.querySelectorAll('input[name="proExam[]"]').forEach((input) => {
      input.disabled = !isEnabled;
      if (!isEnabled) input.checked = false;
    });

    if (proExamYearSelect) {
      proExamYearSelect.required = isEnabled;
      if (!isEnabled) proExamYearSelect.value = "";
    }

    if (proExamReasonSelect) {
      proExamReasonSelect.required = isEnabled;
      if (!isEnabled) proExamReasonSelect.value = "";
    }

    if (trainingCountSelect) {
      trainingCountSelect.required = isEnabled;
      if (!isEnabled) trainingCountSelect.value = "";
    }

    if (proExamOtherContainer) proExamOtherContainer.classList.add("hidden");

    const proExamOtherInput = document.getElementById("proExamOther");
    if (proExamOtherInput) {
      proExamOtherInput.disabled = !isEnabled;
      proExamOtherInput.required = false;
      if (!isEnabled) proExamOtherInput.value = "";
    }
  }

  function syncEducationalBackgroundSection() {
    const selectedChoice = document.querySelector('input[name="hasEducationalBackground"]:checked')?.value || "";
    const shouldShow = selectedChoice === "Yes";

    if (educationBackgroundFields) {
      educationBackgroundFields.classList.toggle("hidden", !shouldShow);
    }

    setEducationalBackgroundEnabled(shouldShow);
  }

  if (educationBackgroundChoiceInputs.length) {
    educationBackgroundChoiceInputs.forEach((input) => {
      input.addEventListener("change", syncEducationalBackgroundSection);
    });
    syncEducationalBackgroundSection();
  }

  // ==================== SCHOLARSHIP DATA UI ====================
  (function setupScholarshipUI() {
    const scholarshipContainer = document.getElementById("scholarshipContainer");
    if (!scholarshipContainer) return;

    const scholarshipNameOptions = [
      "Institutional Scholarship",
      "ACEF - GIAHEP",
      "CHED Full Merit",
      "CHED Half Merit",
      "Crop Protection Association of the Philippines",
      "Del Monte Foundation",
      "DOST - RA 7687 - RA 10612",
      "CHED Tulong Dunong",
      "CHED Tertiary Education Subsidy",
      "GSIS",
      "Insular Foundation",
      "CHED-SSGP-SRA",
      "CHED K-12 Transition Program",
      "CHED SIKAP",
      "DOLE",
      "Other",
    ];

    scholarshipContainer.innerHTML = "";
    scholarshipContainer.classList.add("space-y-4");

    const questionBlock = document.createElement("div");
    questionBlock.classList.add("space-y-4");

    const questionHeading = document.createElement("p");
    questionHeading.classList.add("registration-question");
    questionHeading.innerText = "Did you receive a scholarship?";

    const radioRow = document.createElement("div");
    radioRow.classList.add("registration-radio-group");

    const yesWrapper = document.createElement("label");
    yesWrapper.htmlFor = "scholarYes";
    yesWrapper.classList.add("registration-radio-option");
    const yesRadio = document.createElement("input");
    yesRadio.type = "radio";
    yesRadio.name = "hasScholarship";
    yesRadio.id = "scholarYes";
    yesRadio.value = "Yes";
    yesRadio.required = true;
    const yesLabel = document.createElement("span");
    yesLabel.innerText = "Yes";
    yesWrapper.append(yesRadio, yesLabel);

    const noWrapper = document.createElement("label");
    noWrapper.htmlFor = "scholarNo";
    noWrapper.classList.add("registration-radio-option");
    const noRadio = document.createElement("input");
    noRadio.type = "radio";
    noRadio.name = "hasScholarship";
    noRadio.id = "scholarNo";
    noRadio.value = "No";
    noRadio.required = true;
    const noLabel = document.createElement("span");
    noLabel.innerText = "No";
    noWrapper.append(noRadio, noLabel);

    radioRow.append(yesWrapper, noWrapper);
    questionBlock.append(questionHeading, radioRow);

    const detailsDiv = document.createElement("div");
    detailsDiv.id = "scholarshipFields";
    detailsDiv.classList.add("hidden", "space-y-4");

    const typeWrapper = document.createElement("div");
    typeWrapper.classList.add("field-pair", "flex", "flex-col", "gap-1");
    const typeLabel = document.createElement("label");
    typeLabel.innerText = "Name of Scholarship:";

    const typeSelect = document.createElement("select");
    typeSelect.id = "scholarshipName";
    typeSelect.name = "scholarshipName";

    ["", ...scholarshipNameOptions].forEach((opt) => {
      typeSelect.add(new Option(opt === "" ? "Select Scholarship Name" : opt, opt));
    });
    typeWrapper.append(typeLabel, typeSelect);

    const otherWrapper = document.createElement("div");
    otherWrapper.classList.add("field-pair", "hidden", "flex", "flex-col", "gap-1");
    const otherLabel = document.createElement("label");
    otherLabel.htmlFor = "scholarshipOther";
    otherLabel.innerText = "If Other, specify scholarship name:";
    const otherInput = document.createElement("input");
    otherInput.type = "text";
    otherInput.id = "scholarshipOther";
    otherInput.name = "scholarshipOther";
    otherInput.placeholder = "Enter scholarship name";
    otherWrapper.append(otherLabel, otherInput);

    const yearWrapper = document.createElement("div");
    yearWrapper.classList.add("field-pair", "flex", "flex-col", "gap-1");
    const yearLabel = document.createElement("label");
    yearLabel.innerText = "Year Awarded:";

    const yearSelect = document.createElement("select");
    yearSelect.id = "scholarshipYear";
    yearSelect.name = "scholarshipYear";
    yearWrapper.append(yearLabel, yearSelect);

    const helpedWrapper = document.createElement("div");
    helpedWrapper.classList.add("field-pair", "flex", "flex-col", "gap-3");
    const helpedLabel = document.createElement("p");
    helpedLabel.classList.add("registration-question");
    helpedLabel.innerText = "Did your scholarship help you get a job after graduation?";

    const helpedRadioRow = document.createElement("div");
    helpedRadioRow.classList.add("registration-radio-group");

    const helpedYesWrapper = document.createElement("label");
    helpedYesWrapper.htmlFor = "scholarshipHelpedJobYes";
    helpedYesWrapper.classList.add("registration-radio-option");
    const helpedYesRadio = document.createElement("input");
    helpedYesRadio.type = "radio";
    helpedYesRadio.name = "scholarshipHelpedJob";
    helpedYesRadio.id = "scholarshipHelpedJobYes";
    helpedYesRadio.value = "Yes";
    const helpedYesLabel = document.createElement("span");
    helpedYesLabel.innerText = "Yes";
    helpedYesWrapper.append(helpedYesRadio, helpedYesLabel);

    const helpedNoWrapper = document.createElement("label");
    helpedNoWrapper.htmlFor = "scholarshipHelpedJobNo";
    helpedNoWrapper.classList.add("registration-radio-option");
    const helpedNoRadio = document.createElement("input");
    helpedNoRadio.type = "radio";
    helpedNoRadio.name = "scholarshipHelpedJob";
    helpedNoRadio.id = "scholarshipHelpedJobNo";
    helpedNoRadio.value = "No";
    const helpedNoLabel = document.createElement("span");
    helpedNoLabel.innerText = "No";
    helpedNoWrapper.append(helpedNoRadio, helpedNoLabel);

    helpedRadioRow.append(helpedYesWrapper, helpedNoWrapper);
    helpedWrapper.append(helpedLabel, helpedRadioRow);

    detailsDiv.append(typeWrapper, otherWrapper, yearWrapper, helpedWrapper);

    scholarshipContainer.append(questionBlock, detailsDiv);

    [yesRadio, noRadio].forEach((r) => {
      r.addEventListener("change", function () {
        if (this.value === "Yes") {
          detailsDiv.classList.remove("hidden");
          typeSelect.required = true;
          yearSelect.required = true;
          helpedYesRadio.required = true;
          helpedNoRadio.required = true;
        } else {
          detailsDiv.classList.add("hidden");
          typeSelect.required = false;
          yearSelect.required = false;
          helpedYesRadio.required = false;
          helpedNoRadio.required = false;
          typeSelect.value = "";
          otherInput.value = "";
          otherInput.required = false;
          otherWrapper.classList.add("hidden");
          helpedYesRadio.checked = false;
          helpedNoRadio.checked = false;
        }
      });
    });

    typeSelect.addEventListener("change", function () {
      if (this.value === "Other") {
        otherWrapper.classList.remove("hidden");
        otherInput.required = true;
      } else {
        otherWrapper.classList.add("hidden");
        otherInput.required = false;
        otherInput.value = "";
      }
    });

    populateYearDropdown("scholarshipYear");
  })();

  // ==================== MASTER DEGREE → MAJOR MAP ====================
  const masterDegreeToMajors = {
    "Master of Science in Agricultural Economics": ["Agricultural Economics"],
    "Master of Science in Agricultural Education": ["Agricultural Education"],
    "Master of Science in Agricultural Extension": ["Agricultural Extension"],
    "Master of Science in Agronomy": ["Agronomy"],
    "Master of Science in Animal Science": ["Animal Science"],
    "Master of Science in Entomology": ["Entomology"],
    "Master of Science in Horticulture": ["Horticulture"],
    "Master of Science in Plant Pathology": ["Plant Pathology"],
    "Master of Science in Soil Science": ["Soil Science"],
    "Master of Science in Plant Breeding": ["Plant Breeding"],
    "Master of Science in Development Communication": ["Development Communication"],
    "Master of Science in Agribusiness Management": ["Agribusiness Management"],
    "Master in Agribusiness Management": ["Agribusiness Management"],
    "Master of Science in Biology": [
      "Animal Biology",
      "Biodiversity and Conservation",
      "Ecology",
      "Genetics",
      "Molecular Biology and Biotechnology",
      "Morphology",
      "Physiology",
      "Plant Biology",
      "Systematics",
      "Taxonomy",
    ],
    "Master of Arts in English": ["English"],
    "Master of Arts in English Language Studies": ["English Language Studies"],
    "Master of Science in Mathematics": ["Mathematics"],
    "Master of Arts in Education": [
      "Administration and Supervision",
      "Educational Administration",
    ],
    "Master of Science in Biology Education": ["Biology Education"],
    "Master of Science in General Science Education": ["General Science Education"],
    "Master of Science in Mathematics Education": ["Mathematics Education"],
    "Master of Arts in Language Education": ["English", "Filipino"],
    "Master in Business Administration": ["Business Administration"],
    "Master in Management (Business Management)": ["Business Management"],
    "Master in Engineering": [
      "Civil Engineering",
      "Electrical Engineering",
      "Mechanical Engineering",
    ],
    "Master of Engineering in Civil Engineering": ["Civil Engineering"],
    "Master of Engineering in Energy and Environment": ["Energy and Environment"],
    "Master of Science in Environmental Management": ["Environmental Management"],
    "Master of Science in Forestry": [
      "Forest Biological Sciences",
      "Forest Resources Management",
      "Wood Science and Technology",
    ],
    "Master of Science in Food Science": ["Food Science"],
    "Master of Science in Home Economics Education": ["Home Economics Education"],
    "Master of Arts in Home Economics Education": ["Home Economics Education"],
    "Master of Arts in Home Economics Extension": ["Home Economics Extension"],
    "Master of Veterinary Science (Research Track)": ["Research Track"],
    "Master of Science in Veterinary Medicine (Academic Track)": ["Academic Track"],
    "N/A": ["N/A"],
  };

  const masterSelect = document.getElementById("master");
  const mastMajorSelect = document.getElementById("mastmajor");
  const masterFields = document.getElementById("masterFields");
  const mastYearSelect = document.getElementById("mastyearGraduated");
  const masterChoiceInputs = document.querySelectorAll('input[name="hasMasteral"]');

  function resetMasterMajorOtherState() {
    const otherMastMajorContainer = document.getElementById("otherMastMajorContainer");
    const otherMastMajorInput = document.getElementById("othermastMajor");

    if (otherMastMajorContainer) otherMastMajorContainer.classList.add("hidden");
    if (otherMastMajorInput) {
      otherMastMajorInput.value = "";
      otherMastMajorInput.required = false;
    }
  }

  function syncMasterOtherVisibility() {
    const masterOtherContainerEl = document.getElementById("masterOtherContainer");
    const otherMasterInput = document.getElementById("otherMaster");
    const shouldShow = !!masterSelect && masterSelect.value === "Other";

    if (masterOtherContainerEl) masterOtherContainerEl.classList.toggle("hidden", !shouldShow);
    if (otherMasterInput) {
      otherMasterInput.required = shouldShow;
      if (!shouldShow) otherMasterInput.value = "";
    }
  }

  function updateMasterMajorOptions() {
    if (!mastMajorSelect) return;

    const selectedDegree = masterSelect ? masterSelect.value : "";
    mastMajorSelect.options.length = 0;

    if (!selectedDegree) {
      mastMajorSelect.add(new Option("Select a master's program first", ""));
      mastMajorSelect.disabled = true;
      resetMasterMajorOtherState();
      return;
    }

    if (selectedDegree === "N/A") {
      mastMajorSelect.add(new Option("N/A", "N/A"));
      mastMajorSelect.value = "N/A";
      mastMajorSelect.disabled = true;
      resetMasterMajorOtherState();
      return;
    }

    mastMajorSelect.add(new Option("Select Major", ""));
    (masterDegreeToMajors[selectedDegree] || []).forEach((major) => {
      mastMajorSelect.add(new Option(major, major));
    });
    mastMajorSelect.add(new Option("Other", "Other"));
    mastMajorSelect.disabled = false;

    if (mastMajorSelect.value === "N/A") {
      mastMajorSelect.value = "";
    }
  }

  function syncMasterSection() {
    const selectedChoice = document.querySelector('input[name="hasMasteral"]:checked')?.value || "";
    const shouldShow = selectedChoice === "Yes";

    if (masterFields) masterFields.classList.toggle("hidden", !shouldShow);
    if (masterSelect) masterSelect.required = shouldShow;
    if (mastYearSelect) mastYearSelect.required = shouldShow;

    if (!selectedChoice) {
      if (masterSelect) masterSelect.value = "";
      if (mastYearSelect) mastYearSelect.value = "";
      syncMasterOtherVisibility();
      updateMasterMajorOptions();
      return;
    }

    if (selectedChoice === "No") {
      if (masterSelect) masterSelect.value = "N/A";
      if (mastYearSelect) mastYearSelect.value = "N/A";
      syncMasterOtherVisibility();
      updateMasterMajorOptions();
      return;
    }

    if (masterSelect && masterSelect.value === "N/A") {
      masterSelect.value = "";
    }

    if (mastYearSelect && mastYearSelect.value === "N/A") {
      mastYearSelect.value = "";
    }

    syncMasterOtherVisibility();
    updateMasterMajorOptions();
  }

  if (masterSelect && mastMajorSelect) {
    masterSelect.options.length = 0;
    masterSelect.add(new Option("Select Master's Program", ""));
    const masterKeys = Object.keys(masterDegreeToMajors).filter((degree) => degree !== "N/A");
    masterKeys.forEach((degree) => {
      masterSelect.add(new Option(degree, degree));
    });
    masterSelect.add(new Option("Other", "Other"));
    masterSelect.add(new Option("N/A", "N/A"));
    updateMasterMajorOptions();
    syncMasterOtherVisibility();

    masterSelect.addEventListener("change", function () {
      updateMasterMajorOptions();
      syncMasterOtherVisibility();
    });
  }

  if (masterChoiceInputs.length) {
    masterChoiceInputs.forEach((input) => {
      input.addEventListener("change", syncMasterSection);
    });
    syncMasterSection();
  }

  // ==================== DOCTORATE DEGREE → MAJOR MAP ====================
  const doctorateDegreeToMajors = {
    "Doctor of Philosophy in Agricultural Economics": ["Agricultural Economics"],
    "Doctor of Philosophy in Agricultural Education": ["Agricultural Education"],
    "Doctor of Philosophy in Agricultural Extension": ["Agricultural Extension"],
    "Doctor of Philosophy in Agronomy": ["Agronomy"],
    "Doctor of Philosophy in Animal Science": ["Animal Science"],
    "Doctor of Philosophy in Entomology": ["Entomology"],
    "Doctor of Philosophy in Horticulture": ["Horticulture"],
    "Doctor of Philosophy in Plant Pathology": ["Plant Pathology"],
    "Doctor of Philosophy in Soil Science": ["Soil Science"],
    "Doctor of Philosophy in Biology": [
      "Animal Biology",
      "Biodiversity and Conservation",
      "Developmental Biology",
      "Ecology",
      "Genetics",
      "Molecular Biology and Biotechnology",
      "Physiology",
      "Plant Biology",
      "Systematics",
    ],
    "Doctor of Philosophy in Mathematical Sciences": ["Mathematics Education"],
    "Doctor of Philosophy in Educational Administration": ["Educational Administration"],
    "N/A": ["N/A"],
  };

  const masterProgramsByCollege = {
    "College of Agriculture": [
      "Master of Science in Agricultural Economics",
      "Master of Science in Agricultural Education",
      "Master of Science in Agricultural Extension",
      "Master of Science in Agronomy",
      "Master of Science in Animal Science",
      "Master of Science in Entomology",
      "Master of Science in Horticulture",
      "Master of Science in Plant Pathology",
      "Master of Science in Soil Science",
      "Master of Science in Plant Breeding",
      "Master of Science in Development Communication",
      "Master of Science in Agribusiness Management",
      "Master in Agribusiness Management",
    ],
    "College of Arts and Sciences": [
      "Master of Science in Biology",
      "Master of Arts in English",
      "Master of Arts in English Language Studies",
      "Master of Science in Mathematics",
    ],
    "College of Business and Management": [
      "Master in Business Administration",
      "Master in Management (Business Management)",
    ],
    "College of Education": [
      "Master of Arts in Education",
      "Master of Science in Biology Education",
      "Master of Science in General Science Education",
      "Master of Science in Mathematics Education",
      "Master of Arts in Language Education",
    ],
    "College of Engineering": [
      "Master in Engineering",
      "Master of Engineering in Civil Engineering",
      "Master of Engineering in Energy and Environment",
    ],
    "College of Forestry and Environmental Science": [
      "Master of Science in Environmental Management",
      "Master of Science in Forestry",
    ],
    "College of Human Ecology": [
      "Master of Science in Food Science",
      "Master of Science in Home Economics Education",
      "Master of Arts in Home Economics Education",
      "Master of Arts in Home Economics Extension",
    ],
    "College of Veterinary Medicine": [
      "Master of Veterinary Science (Research Track)",
      "Master of Science in Veterinary Medicine (Academic Track)",
    ],
  };

  const doctorateProgramsByCollege = {
    "College of Agriculture": [
      "Doctor of Philosophy in Agricultural Economics",
      "Doctor of Philosophy in Agricultural Education",
      "Doctor of Philosophy in Agricultural Extension",
      "Doctor of Philosophy in Agronomy",
      "Doctor of Philosophy in Animal Science",
      "Doctor of Philosophy in Entomology",
      "Doctor of Philosophy in Horticulture",
      "Doctor of Philosophy in Plant Pathology",
      "Doctor of Philosophy in Soil Science",
    ],
    "College of Arts and Sciences": [
      "Doctor of Philosophy in Biology",
      "Doctor of Philosophy in Mathematical Sciences",
    ],
    "College of Education": [
      "Doctor of Philosophy in Educational Administration",
    ],
  };

  const verificationProgramsByCollege = {
    "College of Agriculture": {
      undergraduate: undergraduateProgramsByCollege["College of Agriculture"],
      master: masterProgramsByCollege["College of Agriculture"],
      doctorate: doctorateProgramsByCollege["College of Agriculture"],
      nondegree: [],
    },
    "College of Arts and Sciences": {
      undergraduate: undergraduateProgramsByCollege["College of Arts and Sciences"],
      master: masterProgramsByCollege["College of Arts and Sciences"],
      doctorate: doctorateProgramsByCollege["College of Arts and Sciences"],
      nondegree: [],
    },
    "College of Business and Management": {
      undergraduate: undergraduateProgramsByCollege["College of Business and Management"],
      master: masterProgramsByCollege["College of Business and Management"],
      doctorate: [],
      nondegree: [],
    },
    "College of Education": {
      undergraduate: undergraduateProgramsByCollege["College of Education"],
      master: masterProgramsByCollege["College of Education"],
      doctorate: doctorateProgramsByCollege["College of Education"],
      nondegree: [],
    },
    "College of Engineering": {
      undergraduate: undergraduateProgramsByCollege["College of Engineering"],
      master: masterProgramsByCollege["College of Engineering"],
      doctorate: [],
      nondegree: [],
    },
    "College of Forestry and Environmental Science": {
      undergraduate: undergraduateProgramsByCollege["College of Forestry and Environmental Science"],
      master: masterProgramsByCollege["College of Forestry and Environmental Science"],
      doctorate: [],
      nondegree: [],
    },
    "College of Human Ecology": {
      undergraduate: undergraduateProgramsByCollege["College of Human Ecology"],
      master: masterProgramsByCollege["College of Human Ecology"],
      doctorate: [],
      nondegree: [],
    },
    "College of Information Sciences and Computing": {
      undergraduate: undergraduateProgramsByCollege["College of Information Sciences and Computing"],
      master: [],
      doctorate: [],
      nondegree: [],
    },
    "College of Nursing": {
      undergraduate: undergraduateProgramsByCollege["College of Nursing"],
      master: [],
      doctorate: [],
      nondegree: [],
    },
    "College of Veterinary Medicine": {
      undergraduate: undergraduateProgramsByCollege["College of Veterinary Medicine"],
      master: masterProgramsByCollege["College of Veterinary Medicine"],
      doctorate: [],
      nondegree: [],
    },
    "Non-Degree Programs": {
      undergraduate: [],
      master: [],
      doctorate: [],
      nondegree: nonDegreeProgramOptions.filter((program) => !["Other", "N/A"].includes(program)),
    },
  };

  const verificationUndergraduateProgramsSet = new Set(
    Object.values(undergraduateProgramsByCollege)
      .flat()
      .filter((program) => !["Other", "N/A"].includes(program))
      .map((program) => normalizeVerificationProgramValue(program))
  );
  const verificationMasterProgramsSet = new Set(
    Object.values(masterProgramsByCollege)
      .flat()
      .map((program) => normalizeVerificationProgramValue(program))
  );
  const verificationDoctorateProgramsSet = new Set(
    Object.values(doctorateProgramsByCollege)
      .flat()
      .map((program) => normalizeVerificationProgramValue(program))
  );
  const verificationNonDegreeProgramsSet = new Set(
    nonDegreeProgramOptions
      .filter((program) => !["Other", "N/A"].includes(program))
      .map((program) => normalizeVerificationProgramValue(program))
  );

  const doctorateSelect = document.getElementById("doctorate");
  const doctMajorSelect = document.getElementById("doctmajor");
  const doctorateFields = document.getElementById("doctorateFields");
  const doctYearSelect = document.getElementById("doctyearGraduated");
  const doctorateChoiceInputs = document.querySelectorAll('input[name="hasDoctorate"]');

  function resetDoctorateMajorOtherState() {
    const otherDoctMajorContainer = document.getElementById("otherDoctMajorContainer");
    const otherDoctMajorInput = document.getElementById("otherdoctMajor");

    if (otherDoctMajorContainer) otherDoctMajorContainer.classList.add("hidden");
    if (otherDoctMajorInput) {
      otherDoctMajorInput.value = "";
      otherDoctMajorInput.required = false;
    }
  }

  function syncDoctorateOtherVisibility() {
    const doctorateOtherContainerEl = document.getElementById("doctorateOtherContainer");
    const otherDoctorateInput = document.getElementById("otherDoctorate");
    const shouldShow = !!doctorateSelect && doctorateSelect.value === "Other";

    if (doctorateOtherContainerEl) doctorateOtherContainerEl.classList.toggle("hidden", !shouldShow);
    if (otherDoctorateInput) {
      otherDoctorateInput.required = shouldShow;
      if (!shouldShow) otherDoctorateInput.value = "";
    }
  }

  function updateDoctorateMajorOptions() {
    if (!doctMajorSelect) return;

    const selectedDegree = doctorateSelect ? doctorateSelect.value : "";
    doctMajorSelect.options.length = 0;

    if (!selectedDegree) {
      doctMajorSelect.add(new Option("Select a doctorate program first", ""));
      doctMajorSelect.disabled = true;
      resetDoctorateMajorOtherState();
      return;
    }

    if (selectedDegree === "N/A") {
      doctMajorSelect.add(new Option("N/A", "N/A"));
      doctMajorSelect.value = "N/A";
      doctMajorSelect.disabled = true;
      resetDoctorateMajorOtherState();
      return;
    }

    doctMajorSelect.add(new Option("Select Major", ""));
    (doctorateDegreeToMajors[selectedDegree] || []).forEach((major) => {
      doctMajorSelect.add(new Option(major, major));
    });
    doctMajorSelect.add(new Option("Other", "Other"));
    doctMajorSelect.disabled = false;

    if (doctMajorSelect.value === "N/A") {
      doctMajorSelect.value = "";
    }
  }

  function syncDoctorateSection() {
    const selectedChoice = document.querySelector('input[name="hasDoctorate"]:checked')?.value || "";
    const shouldShow = selectedChoice === "Yes";

    if (doctorateFields) doctorateFields.classList.toggle("hidden", !shouldShow);
    if (doctorateSelect) doctorateSelect.required = shouldShow;
    if (doctYearSelect) doctYearSelect.required = shouldShow;

    if (!selectedChoice) {
      if (doctorateSelect) doctorateSelect.value = "";
      if (doctYearSelect) doctYearSelect.value = "";
      syncDoctorateOtherVisibility();
      updateDoctorateMajorOptions();
      return;
    }

    if (selectedChoice === "No") {
      if (doctorateSelect) doctorateSelect.value = "N/A";
      if (doctYearSelect) doctYearSelect.value = "N/A";
      syncDoctorateOtherVisibility();
      updateDoctorateMajorOptions();
      return;
    }

    if (doctorateSelect && doctorateSelect.value === "N/A") {
      doctorateSelect.value = "";
    }

    if (doctYearSelect && doctYearSelect.value === "N/A") {
      doctYearSelect.value = "";
    }

    syncDoctorateOtherVisibility();
    updateDoctorateMajorOptions();
  }

  if (doctorateSelect && doctMajorSelect) {
    doctorateSelect.options.length = 0;
    doctorateSelect.add(new Option("Select Doctorate Program", ""));
    const doctorateKeys = Object.keys(doctorateDegreeToMajors).filter((degree) => degree !== "N/A");
    doctorateKeys.forEach((degree) => {
      doctorateSelect.add(new Option(degree, degree));
    });
    doctorateSelect.add(new Option("Other", "Other"));
    doctorateSelect.add(new Option("N/A", "N/A"));
    updateDoctorateMajorOptions();
    syncDoctorateOtherVisibility();

    doctorateSelect.addEventListener("change", function () {
      updateDoctorateMajorOptions();
      syncDoctorateOtherVisibility();
    });
  }

  if (doctorateChoiceInputs.length) {
    doctorateChoiceInputs.forEach((input) => {
      input.addEventListener("change", syncDoctorateSection);
    });
    syncDoctorateSection();
  }

  // ==================== COUNTRY OTHER HANDLING ====================
  const countrySelect = document.getElementById("country");
  const regionOfOriginSelect = document.getElementById("regionOfOrigin");
  const municipalityCityInput = document.getElementById("municipalityCity");
  const municipalityCityOptions = document.getElementById("municipalityCityOptions");
  const municipalityCityHint = document.getElementById("municipalityCityHint");
  const countryOtherContainer = document.getElementById("countryOtherContainer");

  function setMunicipalityCityHint(message = "", tone = "neutral") {
    if (!municipalityCityHint) return;

    municipalityCityHint.textContent = message;
    municipalityCityHint.classList.toggle("hidden", !message);
    municipalityCityHint.classList.remove("text-gray-500", "text-emerald-700", "text-rose-600");

    if (!message || tone === "neutral") {
      municipalityCityHint.classList.add("text-gray-500");
      return;
    }

    if (tone === "valid") {
      municipalityCityHint.classList.add("text-emerald-700");
      return;
    }

    municipalityCityHint.classList.add("text-rose-600");
  }

  function getRegionXMunicipalitySuggestions(value) {
    const normalized = normalizeLocationValue(value);
    if (!normalized) return [];

    const prefixMatches = [];
    const containsMatches = [];

    regionXMunicipalityOptions.forEach((option) => {
      const normalizedOption = normalizeLocationValue(option);

      if (normalizedOption === normalized || normalizedOption.startsWith(normalized)) {
        prefixMatches.push(option);
        return;
      }

      if (normalizedOption.includes(normalized)) {
        containsMatches.push(option);
      }
    });

    return [...prefixMatches, ...containsMatches];
  }

  function syncMunicipalityCityLiveDetection({ forceValidation = false } = {}) {
    if (!municipalityCityInput || !regionOfOriginSelect || !countrySelect) {
      return { ok: true, firstInvalid: null, message: "" };
    }

    const countryValue = (countrySelect.value || "").trim();
    const regionValue = (regionOfOriginSelect.value || "").trim();
    const municipalityValue = (municipalityCityInput.value || "").trim();
    const isPhilippines = countryValue === "Philippines";
    const regionXSelected = isPhilippines && isRegionXSelection(regionValue);
    const regionXMunicipality = getRegionXMunicipalityMatch(municipalityValue);

    if (!isPhilippines) {
      setMunicipalityCityHint("");
      clearInvalid(municipalityCityInput);
      return { ok: true, firstInvalid: null, message: "" };
    }

    if (!regionValue) {
      setMunicipalityCityHint("Select your region of origin first, then enter your municipality or city.");
      clearInvalid(municipalityCityInput);
      return { ok: true, firstInvalid: null, message: "" };
    }

    if (municipalityValue === "") {
      setMunicipalityCityHint(
        regionXSelected
          ? "Only municipalities and cities from Region X - Northern Mindanao are allowed for this selection."
          : "The municipality or city should match the selected Philippine region."
      );
      clearInvalid(municipalityCityInput);
      return { ok: true, firstInvalid: null, message: "" };
    }

    if (regionXSelected) {
      if (regionXMunicipality) {
        if (forceValidation && municipalityCityInput.value !== regionXMunicipality) {
          municipalityCityInput.value = regionXMunicipality;
        }

        clearInvalid(municipalityCityInput);
        setMunicipalityCityHint(
          `Detected: ${regionXMunicipality} belongs to Region X - Northern Mindanao.`,
          "valid"
        );

        return { ok: true, firstInvalid: null, message: "" };
      }

      const suggestions = getRegionXMunicipalitySuggestions(municipalityValue);
      if (suggestions.length > 0) {
        const preview = suggestions.slice(0, 3).join(", ");
        const suggestionMessage = suggestions.length === 1
          ? `Possible Region X match detected: ${suggestions[0]}.`
          : `Possible Region X matches: ${preview}${suggestions.length > 3 ? "..." : ""}`;

        clearInvalid(municipalityCityInput);
        setMunicipalityCityHint(suggestionMessage);
        return { ok: true, firstInvalid: null, message: "" };
      }

      const invalidMessage = "No Region X municipality/city match detected yet. Please choose a valid Region X entry.";

      if (forceValidation || municipalityValue.length >= 3) {
        markInvalid(municipalityCityInput);
        clearFieldError(municipalityCityInput);
        setMunicipalityCityHint(invalidMessage, "invalid");
        return {
          ok: false,
          firstInvalid: municipalityCityInput,
          message: "Please choose a municipality or city from Region X - Northern Mindanao.",
        };
      }

      clearInvalid(municipalityCityInput);
      setMunicipalityCityHint("Only municipalities and cities from Region X - Northern Mindanao are allowed for this selection.");
      return { ok: true, firstInvalid: null, message: "" };
    }

    if (regionXMunicipality) {
      const mismatchMessage = `${regionXMunicipality} is detected under Region X - Northern Mindanao. Please select Region X or enter a municipality from the selected region.`;
      markInvalid(municipalityCityInput);
      clearFieldError(municipalityCityInput);
      setMunicipalityCityHint(mismatchMessage, "invalid");
      return {
        ok: false,
        firstInvalid: municipalityCityInput,
        message: mismatchMessage,
      };
    }

    clearInvalid(municipalityCityInput);
    setMunicipalityCityHint("The municipality or city should match the selected Philippine region.");
    return { ok: true, firstInvalid: null, message: "" };
  }

  function syncMunicipalityCityOptions() {
    if (!municipalityCityInput) return;

    const countryValue = (countrySelect?.value || "").trim();
    const regionValue = (regionOfOriginSelect?.value || "").trim();
    const isPhilippines = countryValue === "Philippines";
    const regionXSelected = isPhilippines && isRegionXSelection(regionValue);

    if (municipalityCityOptions) {
      municipalityCityOptions.innerHTML = "";
    }

    municipalityCityInput.placeholder = "Enter municipality/city";

    if (!isPhilippines) {
      municipalityCityInput.removeAttribute("list");
      syncMunicipalityCityLiveDetection();
      return;
    }

    if (regionXSelected) {
      municipalityCityInput.setAttribute("list", "municipalityCityOptions");
      municipalityCityInput.placeholder = "Select or type a Region X municipality/city";

      if (municipalityCityOptions) {
        regionXMunicipalityOptions.forEach((option) => {
          const datalistOption = document.createElement("option");
          datalistOption.value = option;
          municipalityCityOptions.appendChild(datalistOption);
        });
      }
      syncMunicipalityCityLiveDetection();
      return;
    }

    municipalityCityInput.removeAttribute("list");

    if (getRegionXMunicipalityMatch(municipalityCityInput.value)) {
      syncMunicipalityCityLiveDetection({ forceValidation: true });
      return;
    }

    syncMunicipalityCityLiveDetection();
  }

  function validateMunicipalityRegionSelection(fieldset = null) {
    if (!municipalityCityInput || !regionOfOriginSelect || !countrySelect) {
      return { ok: true, firstInvalid: null, message: "" };
    }

    if (
      fieldset
      && !fieldset.contains(municipalityCityInput)
      && !fieldset.contains(regionOfOriginSelect)
      && !fieldset.contains(countrySelect)
    ) {
      return { ok: true, firstInvalid: null, message: "" };
    }

    return syncMunicipalityCityLiveDetection({ forceValidation: true });
  }

  if (countrySelect && countryOtherContainer) {
    countrySelect.addEventListener("change", function () {
      countryOtherContainer.classList.toggle("hidden", this.value !== "Other");

      const otherInput = document.getElementById("countryOther");
      if (this.value !== "Other" && otherInput) otherInput.value = "";
      syncMunicipalityCityOptions();
    });
  }

  if (regionOfOriginSelect) {
    regionOfOriginSelect.addEventListener("change", syncMunicipalityCityOptions);
  }

  if (municipalityCityInput) {
    municipalityCityInput.addEventListener("input", function () {
      syncMunicipalityCityLiveDetection();
    });

    municipalityCityInput.addEventListener("change", function () {
      syncMunicipalityCityLiveDetection({ forceValidation: true });
    });

    municipalityCityInput.addEventListener("blur", function () {
      syncMunicipalityCityLiveDetection({ forceValidation: true });
    });
  }

  syncMunicipalityCityOptions();

  // ==================== DEPARTMENT/DEGREE OTHER HANDLING ====================
  const departmentSelect = document.getElementById("department");
  const departmentOtherContainer = document.getElementById("departmentOtherContainer");
  const departmentOtherInput = document.getElementById("departmentOther");

  if (departmentSelect && departmentOtherContainer && departmentOtherInput) {
    departmentSelect.addEventListener("change", function () {
      const shouldShow = this.value === "Other";
      departmentOtherContainer.classList.toggle("hidden", !shouldShow);
      departmentOtherInput.required = shouldShow;
      if (!shouldShow) departmentOtherInput.value = "";
    });
    departmentOtherContainer.classList.add("hidden");
    departmentOtherInput.required = false;
  }

  const degreeSelect = document.getElementById("degree");
  const degreeOtherContainer = document.getElementById("degreeOtherContainer");
  const degreeOtherInput = document.getElementById("degreeOther");

  if (degreeSelect && degreeOtherContainer && degreeOtherInput) {
    degreeSelect.addEventListener("change", function () {
      const shouldShow = this.value === "Other";
      degreeOtherContainer.classList.toggle("hidden", !shouldShow);
      degreeOtherInput.required = shouldShow;
      if (!shouldShow) degreeOtherInput.value = "";
    });
    degreeOtherContainer.classList.add("hidden");
    degreeOtherInput.required = false;
  }

  // ==================== NON-DEGREE OTHER HANDLING ====================
  const nondegreeSelect = document.getElementById("nondegree");
  const nondegreeOtherContainer = document.getElementById("nondegreeOtherContainer");
  const nondegreeFields = document.getElementById("nondegreeFields");
  const nondegreeYearSelect = document.getElementById("nondegreeyearGraduated");
  const nondegreeChoiceInputs = document.querySelectorAll('input[name="hasNonDegree"]');
  let syncNondegreeSection = function () {};

  if (nondegreeSelect && nondegreeOtherContainer) {
    const syncNondegreeOtherVisibility = function () {
      const otherInput = document.getElementById("nondegreeOther");
      const shouldShow = nondegreeSelect.value === "Other";

      nondegreeOtherContainer.classList.toggle("hidden", !shouldShow);
      if (otherInput) {
        otherInput.required = shouldShow;
        if (!shouldShow) otherInput.value = "";
      }
    };

    syncNondegreeSection = function () {
      const selectedChoice = document.querySelector('input[name="hasNonDegree"]:checked')?.value || "";
      const shouldShow = selectedChoice === "Yes";

      if (nondegreeFields) nondegreeFields.classList.toggle("hidden", !shouldShow);
      if (nondegreeSelect) nondegreeSelect.required = shouldShow;
      if (nondegreeYearSelect) nondegreeYearSelect.required = shouldShow;

      if (!selectedChoice) {
        nondegreeSelect.value = "";
        if (nondegreeYearSelect) nondegreeYearSelect.value = "";
        syncNondegreeOtherVisibility();
        return;
      }

      if (selectedChoice === "No") {
        nondegreeSelect.value = "N/A";
        if (nondegreeYearSelect) nondegreeYearSelect.value = "N/A";
        syncNondegreeOtherVisibility();
        return;
      }

      if (nondegreeSelect.value === "N/A") nondegreeSelect.value = "";
      if (nondegreeYearSelect && nondegreeYearSelect.value === "N/A") nondegreeYearSelect.value = "";
      syncNondegreeOtherVisibility();
    };

    nondegreeSelect.addEventListener("change", syncNondegreeOtherVisibility);
    syncNondegreeOtherVisibility();

    if (nondegreeChoiceInputs.length) {
      nondegreeChoiceInputs.forEach((input) => {
        input.addEventListener("change", syncNondegreeSection);
      });
      syncNondegreeSection();
    }
  }

  // ==================== MASTER OTHER HANDLING ====================
  const masterOtherContainer = document.getElementById("masterOtherContainer");

  if (masterSelect && masterOtherContainer) {
    masterSelect.addEventListener("change", function () {
      const shouldShow = this.value === "Other";
      masterOtherContainer.classList.toggle("hidden", !shouldShow);

      const otherInput = document.getElementById("otherMaster");
      if (otherInput) {
        otherInput.required = shouldShow;
        if (!shouldShow) otherInput.value = "";
      }
    });
  }

  // ==================== UNDERGRAD MAJOR OTHER HANDLING ====================
  const undergraduateFields = document.getElementById("undergraduateFields");
  const undergradChoiceInputs = document.querySelectorAll('input[name="hasUndergraduateDegree"]');
  const undergradDepartmentSelect = document.getElementById("department");
  const undergradDegreeSelect = document.getElementById("degree");
  const undergradMajorSelect = document.getElementById("major");
  const undergradYearSelect = document.getElementById("ugyearGraduated");
  const otherMajorContainer = document.getElementById("otherMajorContainer");
  const otherMajorInput = document.getElementById("otherMajor");

  function syncUndergraduateRequiredState() {
    const shouldShow = undergraduateSectionIsActive();

    if (undergradDepartmentSelect) {
      undergradDepartmentSelect.required = shouldShow;
    }

    if (undergradDegreeSelect) {
      undergradDegreeSelect.required = shouldShow && !undergradDegreeSelect.disabled;
    }

    if (undergradMajorSelect) {
      undergradMajorSelect.required = shouldShow && !undergradMajorSelect.disabled;
    }

    if (undergradYearSelect) {
      undergradYearSelect.required = shouldShow;
    }
  }

  function syncUndergraduateDependentFields() {
    const departmentOtherContainerEl = document.getElementById("departmentOtherContainer");
    const departmentOtherInputEl = document.getElementById("departmentOther");
    const degreeOtherContainerEl = document.getElementById("degreeOtherContainer");
    const degreeOtherInputEl = document.getElementById("degreeOther");
    const showDepartmentOther = !!undergradDepartmentSelect && !undergradDepartmentSelect.disabled && undergradDepartmentSelect.value === "Other";
    const showDegreeOther = !!undergradDegreeSelect && !undergradDegreeSelect.disabled && undergradDegreeSelect.value === "Other";
    const showMajorOther = !!undergradMajorSelect && !undergradMajorSelect.disabled && undergradMajorSelect.value === "Other";

    if (departmentOtherContainerEl) departmentOtherContainerEl.classList.toggle("hidden", !showDepartmentOther);
    if (departmentOtherInputEl) {
      departmentOtherInputEl.required = showDepartmentOther;
      if (!showDepartmentOther) departmentOtherInputEl.value = "";
    }

    if (degreeOtherContainerEl) degreeOtherContainerEl.classList.toggle("hidden", !showDegreeOther);
    if (degreeOtherInputEl) {
      degreeOtherInputEl.required = showDegreeOther;
      if (!showDegreeOther) degreeOtherInputEl.value = "";
    }

    if (otherMajorContainer) otherMajorContainer.classList.toggle("hidden", !showMajorOther);
    if (otherMajorInput) {
      otherMajorInput.required = showMajorOther;
      if (!showMajorOther) otherMajorInput.value = "";
    }

    syncUndergraduateRequiredState();
  }

  function updateUndergraduateMajorOptions(preferredMajor = "") {
    if (!undergradMajorSelect) return;

    const sectionActive = undergraduateSectionIsActive();
    const selectedDegree = undergradDegreeSelect ? undergradDegreeSelect.value : "";
    const existingMajor = preferredMajor || undergradMajorSelect.value || "";

    undergradMajorSelect.options.length = 0;

    if (!selectedDegree) {
      undergradMajorSelect.add(new Option("Select degree first", ""));
      undergradMajorSelect.value = "";
      undergradMajorSelect.disabled = true;
      syncUndergraduateDependentFields();
      return;
    }

    if (selectedDegree === "Other") {
      undergradMajorSelect.add(new Option("Other", "Other"));
      undergradMajorSelect.value = "Other";
      undergradMajorSelect.disabled = !sectionActive;
      syncUndergraduateDependentFields();
      return;
    }

    const majors = getUndergraduateMajorsForDegree(selectedDegree);
    const hasSpecificMajorChoices = majors.length > 0 && !(majors.length === 1 && majors[0] === "N/A");

    if (!hasSpecificMajorChoices) {
      undergradMajorSelect.add(new Option("N/A", "N/A"));
      undergradMajorSelect.value = "N/A";
      undergradMajorSelect.disabled = true;
      syncUndergraduateDependentFields();
      return;
    }

    if (majors.length === 1) {
      undergradMajorSelect.add(new Option(majors[0], majors[0]));
      undergradMajorSelect.add(new Option("Other", "Other"));
      undergradMajorSelect.value = (existingMajor && (existingMajor === majors[0] || existingMajor === "Other"))
        ? existingMajor
        : majors[0];
    } else {
      undergradMajorSelect.add(new Option("Select Major", ""));
      majors.forEach((major) => {
        undergradMajorSelect.add(new Option(major, major));
      });
      undergradMajorSelect.add(new Option("Other", "Other"));

      if (existingMajor && (majors.includes(existingMajor) || existingMajor === "Other")) {
        undergradMajorSelect.value = existingMajor;
      } else {
        undergradMajorSelect.value = "";
      }
    }

    undergradMajorSelect.disabled = !sectionActive;

    syncUndergraduateDependentFields();
  }

  function updateUndergraduateDegreeOptions(preferredDegree = "", preferredMajor = "") {
    if (!undergradDegreeSelect) return;

    const sectionActive = undergraduateSectionIsActive();
    const selectedCollege = undergradDepartmentSelect ? undergradDepartmentSelect.value : "";
    const existingDegree = preferredDegree || undergradDegreeSelect.value || "";
    const existingMajor = preferredMajor || undergradMajorSelect?.value || "";

    undergradDegreeSelect.options.length = 0;

    if (!selectedCollege) {
      undergradDegreeSelect.add(new Option("Select college first", ""));
      undergradDegreeSelect.value = "";
      undergradDegreeSelect.disabled = true;
      updateUndergraduateMajorOptions();
      syncUndergraduateDependentFields();
      return;
    }

    const degreeOptions = getUndergraduateProgramsForCollege(selectedCollege);

    undergradDegreeSelect.add(new Option("Select Degree", ""));
    degreeOptions.forEach((degree) => {
      undergradDegreeSelect.add(new Option(degree, degree));
    });

    if (!degreeOptions.includes("Other")) {
      undergradDegreeSelect.add(new Option("Other", "Other"));
    }

    undergradDegreeSelect.disabled = !sectionActive;

    if (existingDegree && (degreeOptions.includes(existingDegree) || existingDegree === "Other")) {
      undergradDegreeSelect.value = existingDegree;
    } else {
      undergradDegreeSelect.value = "";
    }

    updateUndergraduateMajorOptions(existingMajor);
    syncUndergraduateDependentFields();
  }

  function syncUndergraduateSection() {
    const selectedChoice = document.querySelector('input[name="hasUndergraduateDegree"]:checked')?.value || "";
    const shouldShow = selectedChoice === "Yes";

    if (undergraduateFields) undergraduateFields.classList.toggle("hidden", !shouldShow);

    [undergradDepartmentSelect, undergradYearSelect].forEach((input) => {
      if (!input) return;
      input.disabled = !shouldShow;
    });

    if (!shouldShow) {
      [undergradDepartmentSelect, undergradDegreeSelect, undergradMajorSelect, undergradYearSelect, otherMajorInput].forEach((input) => {
        if (!input) return;
        input.value = "";
      });

      const departmentOtherInputEl = document.getElementById("departmentOther");
      const degreeOtherInputEl = document.getElementById("degreeOther");
      if (departmentOtherInputEl) {
        departmentOtherInputEl.value = "";
        departmentOtherInputEl.required = false;
        departmentOtherInputEl.disabled = true;
      }
      if (degreeOtherInputEl) {
        degreeOtherInputEl.value = "";
        degreeOtherInputEl.required = false;
        degreeOtherInputEl.disabled = true;
      }
      if (otherMajorInput) {
        otherMajorInput.required = false;
        otherMajorInput.disabled = true;
      }

      if (undergradDegreeSelect) {
        undergradDegreeSelect.options.length = 0;
        undergradDegreeSelect.add(new Option("Select college first", ""));
        undergradDegreeSelect.disabled = true;
      }

      if (undergradMajorSelect) {
        undergradMajorSelect.options.length = 0;
        undergradMajorSelect.add(new Option("Select degree first", ""));
        undergradMajorSelect.disabled = true;
      }

      syncUndergraduateDependentFields();
      validateUndergraduateCollegeMatch();
      return;
    }

    const departmentOtherInputEl = document.getElementById("departmentOther");
    const degreeOtherInputEl = document.getElementById("degreeOther");
    if (departmentOtherInputEl) departmentOtherInputEl.disabled = false;
    if (degreeOtherInputEl) degreeOtherInputEl.disabled = false;
    if (otherMajorInput) otherMajorInput.disabled = false;

    updateUndergraduateDegreeOptions();
    syncUndergraduateDependentFields();
    validateUndergraduateCollegeMatch();
  }

  if (undergradMajorSelect && otherMajorContainer && otherMajorInput) {
    undergradMajorSelect.addEventListener("change", syncUndergraduateDependentFields);
    otherMajorContainer.classList.add("hidden");
    otherMajorInput.required = false;
  }

  if (undergradDepartmentSelect) {
    undergradDepartmentSelect.addEventListener("change", function () {
      updateUndergraduateDegreeOptions();
      validateUndergraduateCollegeMatch();
    });
  }

  if (undergradDegreeSelect) {
    undergradDegreeSelect.addEventListener("change", function () {
      updateUndergraduateMajorOptions();
    });
  }

  if (undergradChoiceInputs.length) {
    undergradChoiceInputs.forEach((input) => {
      input.addEventListener("change", syncUndergraduateSection);
    });
    syncUndergraduateSection();
  }

  if (departmentOtherInput) {
    ["input", "change", "blur"].forEach((eventName) => {
      departmentOtherInput.addEventListener(eventName, function () {
        validateUndergraduateCollegeMatch();
      });
    });
  }

  // ==================== DOCTORATE OTHER HANDLING ====================
  const doctorateOtherContainer = document.getElementById("doctorateOtherContainer");

  if (doctorateSelect && doctorateOtherContainer) {
    doctorateSelect.addEventListener("change", function () {
      const shouldShow = this.value === "Other";
      doctorateOtherContainer.classList.toggle("hidden", !shouldShow);

      const otherInput = document.getElementById("otherDoctorate");
      if (otherInput) {
        otherInput.required = shouldShow;
        if (!shouldShow) otherInput.value = "";
      }
    });
  }

  // ==================== EMPLOYMENT DATA OPTIONS ====================
  const curriculumCompetencies = [
    "Communication skills",
    "Human relations skills",
    "Entrepreneurial skills",
    "Information Technology skills",
    "Problem-solving skills",
    "Critical Thinking skills",
    "Other",
  ];

  const curriculumReasonOptions = [
    "Job was not related to my course",
    "The job needed different skills",
    "I learned more on the job",
    "There was no matching job available",
    "Other",
  ];

  const firstJobTimeOptions = [
    "Less than a month",
    "1 to 6 months",
    "7 to 11 months",
    "1 year to less than 2 years",
    "2 years to less than 3 years",
    "3 years to less than 4 years",
    "N/A",
    "Other",
  ];

  const firstJobRelatedOptions = ["Yes", "No", "N/A"];

  const firstJobHowOptions = [
    "Response to an advertisement",
    "As walk-in applicants",
    "Recommended by someone",
    "Information from friends",
    "Arranged by school's job placement officer",
    "Family Business",
    "Job Fair of Public Employment Service Office (PESO)",
    "N/A",
    "Other",
  ];

  const firstJobEarningsOptions = [
    "Below P5,000.00",
    "P5,000.00 to less than P10,000.00",
    "P10,000.00 to less than P15,000.00",
    "P15,000.00 to less than P20,000.00",
    "P20,000.00 to less than P25,000.00",
    "P25,000.00 and above",
    "N/A",
  ];

  const firstJobDurationOptions = [
    "Less than a month",
    "1 to 6 months",
    "7 to 11 months",
    "1 year to less than 2 years",
    "2 years to less than 3 years",
    "3 years to less than 4 years",
    "More than 4 years",
    "N/A",
    "Other",
  ];

  const presentEmploymentStatusOptions = [
    "Regular or Permanent",
    "Temporary",
    "Casual",
    "Contractual",
    "Self-employed",
    "Job Order",
  ];

  const businessLineOptions = [
    "Agriculture, Hunting, and Forestry",
    "Fishing",
    "Mining and Quarrying",
    "Electricity, Gas and Water Supply",
    "Construction",
    "Wholesale and Retail Trade",
    "Hotels and Restaurants",
    "Transport Storage and Communication",
    "Financial Intermediation",
    "Real Estate, Renting and Business Activities",
    "Public Administration and Defense; Compulsory Social Security",
    "Education",
    "Health and Social Work",
    "Other Community, Social and Personal Service Activities",
    "Private Households with Employed Persons",
    "Other",
  ];

  const jobLevelOptions = [
    "Rank or Clerical",
    "Professional, Technical or Supervisory",
    "Managerial or Executive",
    "Self-employed",
    "Other",
  ];

  const presentJobReasonOptions = [
    "Salaries & Benefits",
    "Career challenge",
    "Related to special skills",
    "Related to course or program of study",
    "Proximity of residence",
    "Peer influence",
    "Other",
  ];

  const presentJobEarningsOptions = [
    "Below P10,000.00",
    "P10,000.00 to less than P20,000.00",
    "P20,000.00 to less than P30,000.00",
    "P30,000.00 to less than P40,000.00",
    "P40,000.00 to less than P50,000.00",
    "P50,000.00 to less than P60,000.00",
    "P60,000.00 to less than P70,000.00",
    "P70,000.00 to less than P80,000.00",
    "P80,000.00 to less than P90,000.00",
    "P90,000.00 to less than P100,000.00",
    "P100,000.00 and above",
  ];

  const facilitiesSatisfactionOptions = [
    "Very Satisfied",
    "Satisfied",
    "Not Satisfied",
  ];

  const agreementScaleOptions = [
    ["5", "Strongly Agree"],
    ["4", "Mostly Agree"],
    ["3", "Neither Agree nor Disagree"],
    ["2", "Mostly Disagree"],
    ["1", "Strongly Disagree"],
  ];

  function populateCheckboxGroup(containerId, inputName, options) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = "";

    options.forEach((option, index) => {
      const label = document.createElement("label");
      label.className = "registration-check-option";
      label.htmlFor = `${containerId}_${index}`;

      const input = document.createElement("input");
      input.type = "checkbox";
      input.id = `${containerId}_${index}`;
      input.name = inputName;
      input.value = option;
      input.addEventListener("change", function () {
        if (Array.from(container.querySelectorAll('input[type="checkbox"]')).some((checkbox) => checkbox.checked)) {
          container.querySelectorAll('input[type="checkbox"]').forEach(clearInvalid);
          clearInlineGroupError(container, inputName);
        }
      });

      const text = document.createElement("span");
      text.textContent = option;

      label.append(input, text);
      container.appendChild(label);
    });
  }

  function getCheckedRadioValue(name) {
    return document.querySelector(`input[name="${name}"]:checked`)?.value || "";
  }

  function setRadioGroupRequired(name, required) {
    document.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
      input.required = required;
      if (!required) {
        clearInvalid(input);
      }
    });
  }

  function clearRadioGroup(name) {
    document.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
      input.checked = false;
      input.required = false;
      clearInvalid(input);
    });
  }

  function setRadioGroupValue(name, value = "") {
    document.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
      input.checked = value !== "" && input.value === value;
    });
  }

  function syncLockedRadioChoice(name, lockedValue = "") {
    const shouldLock = lockedValue !== "";

    document.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
      if (shouldLock && input.value === lockedValue) {
        input.checked = true;
      }

      input.disabled = shouldLock ? input.value !== lockedValue : false;
    });
  }

  function clearCheckboxGroup(name) {
    document.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
      input.checked = false;
      input.disabled = false;
    });
  }

  function syncOtherCheckboxInput(inputName, container, textInput, enabled) {
    if (!container || !textInput) return;

    const show = enabled && Array.from(document.querySelectorAll(`input[name="${inputName}"]`)).some((input) => input.checked && input.value === "Other");
    container.classList.toggle("hidden", !show);
    textInput.required = show;
    textInput.disabled = !show;

    if (!show) {
      textInput.value = "";
    }
  }

  function syncOtherSelect(select, container, input, enabled) {
    if (!select || !container || !input) return;

    const show = enabled && select.value === "Other";
    container.classList.toggle("hidden", !show);
    input.required = show;
    input.disabled = !show;

    if (!show) {
      input.value = "";
    }
  }

  function setFieldRequired(input, required) {
    if (!input) return;
    input.required = required;
  }

  function setupToggleableOptionalRadioGroup(name, onToggleOff) {
    document.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
      const rememberCheckedState = () => {
        input.dataset.wasChecked = input.checked ? "true" : "false";
      };

      const label = input.closest("label");

      input.addEventListener("pointerdown", rememberCheckedState);
      input.addEventListener("mousedown", rememberCheckedState);
      input.addEventListener("keydown", function (event) {
        if (event.key === " " || event.key === "Enter") {
          rememberCheckedState();
        }
      });

      if (label) {
        label.addEventListener("pointerdown", rememberCheckedState);
        label.addEventListener("mousedown", rememberCheckedState);
      }

      input.addEventListener("click", function (event) {
        if (input.dataset.wasChecked !== "true") return;

        event.preventDefault();
        clearRadioGroup(name);
        input.checked = false;
        input.dataset.wasChecked = "false";

        if (typeof onToggleOff === "function") {
          onToggleOff(input);
        }
      });
    });
  }

  function populateAgreementScale(selectId) {
    const select = document.getElementById(selectId);
    if (!select) return;

    agreementScaleOptions.forEach(([value, label]) => {
      select.add(new Option(label, value));
    });
  }

  populateDropdown("curriculumNotRelevantReason", curriculumReasonOptions, "Select reason");
  populateDropdown("firstJobTime", firstJobTimeOptions, "Select time");
  populateDropdown("firstJobRelated", firstJobRelatedOptions, "Select answer");
  populateDropdown("firstJobHow", firstJobHowOptions, "Select method");
  populateDropdown("firstJobEarnings", firstJobEarningsOptions, "Select earnings");
  populateDropdown("firstJobDuration", firstJobDurationOptions, "Select duration");
  populateDropdown("presentEmploymentStatus", presentEmploymentStatusOptions, "Select status");
  populateDropdown("businessLine", businessLineOptions, "Select business line");
  populateDropdown("jobLevel", jobLevelOptions, "Select level");
  populateDropdown("presentJobEarnings", presentJobEarningsOptions, "Select earnings");
  populateDropdown("facilitiesSatisfaction", facilitiesSatisfactionOptions, "Select answer");

  [
    "teachingFacultyEnthusiastic",
    "teachingConceptsClear",
    "teachingKnowledgeable",
    "teachingCommitment",
    "degreeProgramQuality",
    "overallRecommendInstitution",
    "overallObjectivesMet",
    "overallUniversityExperience",
  ].forEach(populateAgreementScale);

  populateCheckboxGroup("firstJobCompetenciesContainer", "firstJobCompetencies[]", curriculumCompetencies);
  populateCheckboxGroup("presentJobReasonsContainer", "presentJobReasons[]", presentJobReasonOptions);

  const curriculumYesContainer = document.getElementById("curriculumYesContainer");
  const curriculumNoContainer = document.getElementById("curriculumNoContainer");
  const firstJobFields = document.getElementById("firstJobFields");
  const firstJobExtendedFields = document.getElementById("firstJobExtendedFields");
  const employmentStatusNote = document.getElementById("employmentStatusNote");
  const employmentDetails = document.getElementById("employmentDetails");
  const employmentExtendedDetails = document.getElementById("employmentExtendedDetails");
  const curriculumReasonSelect = document.getElementById("curriculumNotRelevantReason");
  const curriculumReasonOtherContainer = document.getElementById("curriculumNotRelevantReasonOtherContainer");
  const curriculumReasonOtherInput = document.getElementById("curriculumNotRelevantReasonOther");
  const firstJobCompetenciesOtherContainer = document.getElementById("firstJobCompetenciesOtherContainer");
  const firstJobCompetenciesOtherInput = document.getElementById("firstJobCompetenciesOther");
  const firstJobTimeSelect = document.getElementById("firstJobTime");
  const firstJobTimeOtherContainer = document.getElementById("firstJobTimeOtherContainer");
  const firstJobTimeOtherInput = document.getElementById("firstJobTimeOther");
  const firstJobRelatedSelect = document.getElementById("firstJobRelated");
  const firstJobHowSelect = document.getElementById("firstJobHow");
  const firstJobHowOtherContainer = document.getElementById("firstJobHowOtherContainer");
  const firstJobHowOtherInput = document.getElementById("firstJobHowOther");
  const firstJobEarningsSelect = document.getElementById("firstJobEarnings");
  const firstJobDurationSelect = document.getElementById("firstJobDuration");
  const firstJobDurationOtherContainer = document.getElementById("firstJobDurationOtherContainer");
  const firstJobDurationOtherInput = document.getElementById("firstJobDurationOther");
  const presentEmploymentStatusSelect = document.getElementById("presentEmploymentStatus");
  const presentOccupationInput = document.getElementById("presentOccupation");
  const presentCompanyInput = document.getElementById("presentCompany");
  const directSupervisorInput = document.getElementById("directSupervisor");
  const employerEmailInput = document.getElementById("employerEmail");
  const businessLineSelect = document.getElementById("businessLine");
  const businessLineOtherContainer = document.getElementById("businessLineOtherContainer");
  const businessLineOtherInput = document.getElementById("businessLineOther");
  const employmentProofInput = document.getElementById("employmentProof");
  const jobLevelSelect = document.getElementById("jobLevel");
  const jobLevelOtherContainer = document.getElementById("jobLevelOtherContainer");
  const jobLevelOtherInput = document.getElementById("jobLevelOther");
  const presentJobReasonsOtherContainer = document.getElementById("presentJobReasonsOtherContainer");
  const presentJobReasonsOtherInput = document.getElementById("presentJobReasonsOther");
  const presentJobEarningsSelect = document.getElementById("presentJobEarnings");
  const employeeAwardsContainer = document.getElementById("employeeAwardsContainer");
  const employeeAwardsInput = document.getElementById("employeeAwards");
  const curriculumSuggestionsContainer = document.getElementById("curriculumSuggestionsContainer");
  const curriculumSuggestionsInput = document.getElementById("curriculumSuggestions");
  const employabilitySuggestionsContainer = document.getElementById("employabilitySuggestionsContainer");
  const employabilitySuggestionsInput = document.getElementById("employabilitySuggestions");
  const facilitySuggestionsContainer = document.getElementById("facilitySuggestionsContainer");
  const facilitySuggestionsInput = document.getElementById("facilitySuggestions");
  const firstJobExtendedFieldsClose = document.getElementById("firstJobExtendedFieldsClose");
  const firstJobExtendedFieldsDone = document.getElementById("firstJobExtendedFieldsDone");
  const firstJobExtendedFieldsReview = document.getElementById("firstJobExtendedFieldsReview");
  const employmentExtendedDetailsClose = document.getElementById("employmentExtendedDetailsClose");
  const employmentExtendedDetailsDone = document.getElementById("employmentExtendedDetailsDone");
  const employmentExtendedDetailsReview = document.getElementById("employmentExtendedDetailsReview");
  let firstJobExtendedViewOpen = false;
  let employmentExtendedViewOpen = false;
  let firstJobExtendedViewSaved = false;
  let employmentExtendedViewSaved = false;

  function hasCheckedInput(name) {
    return Array.from(document.querySelectorAll(`input[name="${name}"]`)).some((input) => input.checked);
  }

  function hasFirstJobExtendedValues() {
    return !!firstJobHowSelect?.value
      || !!firstJobHowOtherInput?.value
      || !!firstJobEarningsSelect?.value
      || !!firstJobDurationSelect?.value
      || !!firstJobDurationOtherInput?.value;
  }

  function hasEmploymentExtendedValues() {
    return !!directSupervisorInput?.value
      || !!employerEmailInput?.value
      || !!jobLevelSelect?.value
      || !!jobLevelOtherInput?.value
      || hasCheckedInput("firstJobIsCurrent")
      || Array.from(document.querySelectorAll('input[name="presentJobReasons[]"]')).some((input) => input.checked)
      || !!presentJobReasonsOtherInput?.value
      || hasCheckedInput("hasEmployeeAwards")
      || !!employeeAwardsInput?.value;
  }

  function isFirstJobExtendedComplete() {
    if (getCheckedRadioValue("shareMoreFirstJobDetails") !== "Yes") return false;
    if (!firstJobHowSelect?.value || !firstJobEarningsSelect?.value || !firstJobDurationSelect?.value) return false;
    if (firstJobHowSelect.value === "Other" && !firstJobHowOtherInput?.value?.trim()) return false;
    if (firstJobDurationSelect.value === "Other" && !firstJobDurationOtherInput?.value?.trim()) return false;

    return true;
  }

  function isEmploymentExtendedComplete() {
    if (getCheckedRadioValue("shareMoreEmploymentDetails") !== "Yes") return false;
    if (!jobLevelSelect?.value) return false;
    if (jobLevelSelect.value === "Other" && !jobLevelOtherInput?.value?.trim()) return false;
    if (!getCheckedRadioValue("firstJobIsCurrent")) return false;
    if (!getCheckedRadioValue("hasEmployeeAwards")) return false;
    if (!Array.from(document.querySelectorAll('input[name="presentJobReasons[]"]')).some((input) => input.checked)) return false;
    if (
      Array.from(document.querySelectorAll('input[name="presentJobReasons[]"]')).some((input) => input.checked && input.value === "Other")
      && !presentJobReasonsOtherInput?.value?.trim()
    ) {
      return false;
    }
    if (getCheckedRadioValue("hasEmployeeAwards") === "Yes" && !employeeAwardsInput?.value?.trim()) return false;

    return true;
  }

  function clearFirstJobExtendedFields() {
    [firstJobHowSelect, firstJobEarningsSelect, firstJobDurationSelect].forEach((input) => {
      if (input) input.value = "";
    });

    if (firstJobHowOtherInput) firstJobHowOtherInput.value = "";
    if (firstJobDurationOtherInput) firstJobDurationOtherInput.value = "";
  }

  function clearEmploymentExtendedFields() {
    if (directSupervisorInput) directSupervisorInput.value = "";
    if (employerEmailInput) employerEmailInput.value = "";
    if (jobLevelSelect) jobLevelSelect.value = "";
    if (jobLevelOtherInput) jobLevelOtherInput.value = "";
    if (presentJobReasonsOtherInput) presentJobReasonsOtherInput.value = "";
    if (employeeAwardsInput) employeeAwardsInput.value = "";

    clearRadioGroup("firstJobIsCurrent");
    clearRadioGroup("hasEmployeeAwards");
    document.querySelectorAll('input[name="presentJobReasons[]"]').forEach((input) => {
      input.checked = false;
    });
  }

  function focusFirstInputInContainer(container) {
    if (!container) return;

    const firstInput = Array.from(container.querySelectorAll("input, select, textarea, button")).find((input) => {
      if (input.disabled) return false;
      if (input.closest && input.closest(".hidden")) return false;
      if (input.getClientRects && input.getClientRects().length === 0) return false;
      return true;
    });

    if (firstInput) {
      try {
        firstInput.focus();
      } catch (error) {}
    }
  }

  function setMiniViewTriggerVisibility(trigger, shouldShow) {
    if (!trigger) return;
    trigger.classList.toggle("hidden", !shouldShow);
  }

  function setFirstJobExtendedSaved(saved) {
    firstJobExtendedViewSaved = saved;
    if (saved) clearMiniViewValidationMessage(firstJobExtendedFields);
  }

  function setEmploymentExtendedSaved(saved) {
    employmentExtendedViewSaved = saved;
    if (saved) clearMiniViewValidationMessage(employmentExtendedDetails);
  }

  function setMiniViewValidationMessage(container, message) {
    if (!container || !message) return;

    let messageElement = container.querySelector("[data-registration-mini-view-error]");
    if (!messageElement) {
      messageElement = document.createElement("p");
      messageElement.dataset.registrationMiniViewError = "true";
      messageElement.className = "rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900";
      messageElement.setAttribute("role", "status");
      const header = container.querySelector(".registration-mini-view__header");
      if (header) {
        header.insertAdjacentElement("afterend", messageElement);
      } else {
        container.prepend(messageElement);
      }
    }

    messageElement.textContent = message;
  }

  function clearMiniViewValidationMessage(container) {
    container?.querySelector("[data-registration-mini-view-error]")?.remove();
  }

  function validateRequiredFields(container, multiSelectGroups = []) {
    if (!container) return { ok: true, firstInvalid: null, message: "" };

    const inputs = container.querySelectorAll("input, select, textarea");

    for (const input of inputs) {
      if (input.disabled) continue;
      if (input.type !== "hidden" && input.closest && input.closest(".hidden")) continue;
      if (input.type !== "hidden" && input.getClientRects && input.getClientRects().length === 0) continue;
      if (!input.hasAttribute("required")) continue;

      clearInvalid(input);

      if (input.type === "radio") {
        const checked = container.querySelector(`input[name="${input.name}"]:checked`);
        if (!checked) {
          markInvalid(input, "Please select an option.");
          return {
            ok: false,
            firstInvalid: input,
            message: "Please complete all required fields."
          };
        }
      } else if (input.type === "checkbox") {
        if (!input.checked) {
          markInvalid(input, "Please check this box to continue.");
          return {
            ok: false,
            firstInvalid: input,
            message: "Please complete all required fields."
          };
        }
      } else if (!input.value || !input.value.trim()) {
        markInvalid(input, "This field is required.");
        return {
          ok: false,
          firstInvalid: input,
          message: "Please fill out all required fields."
        };
      }
    }

    for (const group of multiSelectGroups) {
      const groupInputs = Array.from(container.querySelectorAll(`input[name="${group.name}"]`)).filter((input) => {
        if (input.disabled) return false;
        if (input.closest && input.closest(".hidden")) return false;
        if (input.getClientRects && input.getClientRects().length === 0) return false;
        return true;
      });

      if (groupInputs.length > 0 && !groupInputs.some((input) => input.checked)) {
        const groupContainer = groupInputs[0].closest(".registration-check-grid");
        setInlineGroupError(groupContainer, group.name, group.message);
        return {
          ok: false,
          firstInvalid: groupInputs[0],
          message: group.message,
        };
      }
      if (groupInputs.length > 0) {
        clearInlineGroupError(groupInputs[0].closest(".registration-check-grid"), group.name);
      }
    }

    return { ok: true, firstInvalid: null, message: "" };
  }

  function syncCurriculumSection() {
    const selected = getCheckedRadioValue("curriculumRelevant");
    const showYes = selected === "Yes";
    const showNo = selected === "No";

    if (curriculumYesContainer) curriculumYesContainer.classList.toggle("hidden", !showYes);
    if (curriculumNoContainer) curriculumNoContainer.classList.toggle("hidden", !showNo);

    document.querySelectorAll('input[name="firstJobCompetencies[]"]').forEach((input) => {
      input.disabled = !showYes;
      if (!showYes) input.checked = false;
    });

    if (curriculumReasonSelect) {
      curriculumReasonSelect.required = showNo;
      if (!showNo) curriculumReasonSelect.value = "";
    }

    syncOtherCheckboxInput("firstJobCompetencies[]", firstJobCompetenciesOtherContainer, firstJobCompetenciesOtherInput, showYes);
    syncOtherSelect(curriculumReasonSelect, curriculumReasonOtherContainer, curriculumReasonOtherInput, showNo);
  }

  function syncFirstJobSection() {
    const employedAfterGrad = getCheckedRadioValue("employedAfterGrad") === "Yes";
    const wantsExtended = employedAfterGrad && getCheckedRadioValue("shareMoreFirstJobDetails") === "Yes";
    const showExtended = wantsExtended && firstJobExtendedViewOpen;
    const hasExtendedValues = hasFirstJobExtendedValues();

    if (firstJobFields) firstJobFields.classList.toggle("hidden", !employedAfterGrad);

    [
      firstJobTimeSelect,
      firstJobRelatedSelect,
    ].forEach((input) => {
      setFieldRequired(input, employedAfterGrad);
      if (input && !employedAfterGrad) input.value = "";
    });

    if (firstJobExtendedFields) firstJobExtendedFields.classList.toggle("hidden", !showExtended);
    setMiniViewTriggerVisibility(firstJobExtendedFieldsReview, wantsExtended && hasExtendedValues && !showExtended);

    [firstJobHowSelect, firstJobEarningsSelect, firstJobDurationSelect].forEach((input) => {
      setFieldRequired(input, wantsExtended);
    });

    if (!employedAfterGrad) {
      clearRadioGroup("shareMoreFirstJobDetails");
      firstJobExtendedViewOpen = false;
      setFirstJobExtendedSaved(false);
    }

    if (!wantsExtended) {
      firstJobExtendedViewOpen = false;
      setFirstJobExtendedSaved(false);
      clearFirstJobExtendedFields();
    }

    syncOtherSelect(firstJobTimeSelect, firstJobTimeOtherContainer, firstJobTimeOtherInput, employedAfterGrad);
    syncOtherSelect(firstJobHowSelect, firstJobHowOtherContainer, firstJobHowOtherInput, wantsExtended);
    syncOtherSelect(firstJobDurationSelect, firstJobDurationOtherContainer, firstJobDurationOtherInput, wantsExtended);
  }

  function syncPresentEmploymentSection() {
    const presentlyEmployed = getCheckedRadioValue("presentlyEmployed");
    const showDetails = presentlyEmployed === "Yes";
    const wantsExtended = showDetails && getCheckedRadioValue("shareMoreEmploymentDetails") === "Yes";
    const showExtended = wantsExtended && employmentExtendedViewOpen;
    const hasExtendedValues = hasEmploymentExtendedValues();

    if (employmentDetails) employmentDetails.classList.toggle("hidden", !showDetails);
    if (employmentStatusNote) employmentStatusNote.classList.toggle("hidden", showDetails);
    if (employmentExtendedDetails) employmentExtendedDetails.classList.toggle("hidden", !showExtended);
    setMiniViewTriggerVisibility(employmentExtendedDetailsReview, wantsExtended && hasExtendedValues && !showExtended);

    [
      presentEmploymentStatusSelect,
      presentOccupationInput,
      presentCompanyInput,
      businessLineSelect,
      presentJobEarningsSelect,
    ].forEach((input) => {
      setFieldRequired(input, showDetails);
      if (!showDetails && input && input.type !== "file") {
        input.value = "";
      }
    });

    setFieldRequired(employmentProofInput, false);

    setFieldRequired(jobLevelSelect, wantsExtended);
    setRadioGroupRequired("firstJobIsCurrent", wantsExtended);
    setRadioGroupRequired("hasEmployeeAwards", wantsExtended);

    document.querySelectorAll('input[name="presentJobReasons[]"]').forEach((input) => {
      input.disabled = !wantsExtended;
      if (!wantsExtended) input.checked = false;
    });

    if (!showDetails) {
      clearRadioGroup("shareMoreEmploymentDetails");
      employmentExtendedViewOpen = false;
      setEmploymentExtendedSaved(false);
      clearRadioGroup("firstJobIsCurrent");
      clearRadioGroup("hasEmployeeAwards");
      if (employmentProofInput) employmentProofInput.value = "";
      if (jobLevelSelect) jobLevelSelect.value = "";
      if (jobLevelOtherInput) jobLevelOtherInput.value = "";
      if (directSupervisorInput) directSupervisorInput.value = "";
      if (employerEmailInput) employerEmailInput.value = "";
      if (employeeAwardsInput) employeeAwardsInput.value = "";
    }

    if (!wantsExtended) {
      employmentExtendedViewOpen = false;
      setEmploymentExtendedSaved(false);
      clearEmploymentExtendedFields();
    }

    syncOtherSelect(businessLineSelect, businessLineOtherContainer, businessLineOtherInput, showDetails);
    syncOtherSelect(jobLevelSelect, jobLevelOtherContainer, jobLevelOtherInput, wantsExtended);
    syncOtherCheckboxInput("presentJobReasons[]", presentJobReasonsOtherContainer, presentJobReasonsOtherInput, wantsExtended);
    syncEmployeeAwards(wantsExtended);
  }

  function syncEmployeeAwards(forceEmploymentExtended = null) {
    const employmentExtended = forceEmploymentExtended ?? (
      getCheckedRadioValue("presentlyEmployed") === "Yes"
      && (
        getCheckedRadioValue("shareMoreEmploymentDetails") === "Yes"
        || !!jobLevelSelect?.value
        || !!getCheckedRadioValue("firstJobIsCurrent")
        || Array.from(document.querySelectorAll('input[name="presentJobReasons[]"]')).some((input) => input.checked)
        || !!getCheckedRadioValue("hasEmployeeAwards")
        || !!employeeAwardsInput?.value
      )
    );
    const show = employmentExtended && getCheckedRadioValue("hasEmployeeAwards") === "Yes";
    if (employeeAwardsContainer) employeeAwardsContainer.classList.toggle("hidden", !show);
    if (employeeAwardsInput) {
      employeeAwardsInput.required = show;
      if (!show) employeeAwardsInput.value = "";
    }
  }

  function syncSuggestionToggle(radioName, container, input) {
    const radios = document.querySelectorAll(`input[name="${radioName}"]`);

    if (!radios.length) {
      if (container) container.classList.remove("hidden");
      return;
    }

    const show = getCheckedRadioValue(radioName) === "Yes";
    if (container) container.classList.toggle("hidden", !show);
    if (input) {
      input.required = show;
      if (!show) input.value = "";
    }
  }

  document.querySelectorAll('input[name="curriculumRelevant"]').forEach((input) => {
    input.addEventListener("change", syncCurriculumSection);
  });

  document.querySelectorAll('input[name="firstJobCompetencies[]"]').forEach((input) => {
    input.addEventListener("change", function () {
      syncOtherCheckboxInput("firstJobCompetencies[]", firstJobCompetenciesOtherContainer, firstJobCompetenciesOtherInput, getCheckedRadioValue("curriculumRelevant") === "Yes");
    });
  });

  if (curriculumReasonSelect) {
    curriculumReasonSelect.addEventListener("change", function () {
      syncOtherSelect(curriculumReasonSelect, curriculumReasonOtherContainer, curriculumReasonOtherInput, getCheckedRadioValue("curriculumRelevant") === "No");
    });
  }

  document.querySelectorAll('input[name="employedAfterGrad"]').forEach((input) => {
    input.addEventListener("change", function () {
      if (this.value !== "Yes") {
        firstJobExtendedViewOpen = false;
      }
      syncFirstJobSection();
    });
  });

  setupToggleableOptionalRadioGroup("shareMoreFirstJobDetails", function () {
    firstJobExtendedViewOpen = false;
    setFirstJobExtendedSaved(false);
    clearFirstJobExtendedFields();
    syncFirstJobSection();
  });

  document.querySelectorAll('input[name="shareMoreFirstJobDetails"]').forEach((input) => {
    input.addEventListener("change", function () {
      firstJobExtendedViewOpen = this.value === "Yes";
      if (this.value !== "Yes") {
        setFirstJobExtendedSaved(false);
      }
      syncFirstJobSection();

      if (firstJobExtendedViewOpen) {
        focusFirstInputInContainer(firstJobExtendedFields);
      }
    });
  });

  if (firstJobTimeSelect) firstJobTimeSelect.addEventListener("change", syncFirstJobSection);
  if (firstJobHowSelect) firstJobHowSelect.addEventListener("change", syncFirstJobSection);
  if (firstJobDurationSelect) firstJobDurationSelect.addEventListener("change", syncFirstJobSection);
  if (firstJobEarningsSelect) firstJobEarningsSelect.addEventListener("change", syncFirstJobSection);

  document.querySelectorAll('input[name="presentlyEmployed"]').forEach((input) => {
    input.addEventListener("change", function () {
      if (this.value !== "Yes") {
        employmentExtendedViewOpen = false;
      }
      syncPresentEmploymentSection();
    });
  });

  setupToggleableOptionalRadioGroup("shareMoreEmploymentDetails", function () {
    employmentExtendedViewOpen = false;
    setEmploymentExtendedSaved(false);
    clearEmploymentExtendedFields();
    syncPresentEmploymentSection();
  });

  document.querySelectorAll('input[name="shareMoreEmploymentDetails"]').forEach((input) => {
    input.addEventListener("change", function () {
      employmentExtendedViewOpen = this.value === "Yes";
      if (this.value !== "Yes") {
        setEmploymentExtendedSaved(false);
      }
      syncPresentEmploymentSection();

      if (employmentExtendedViewOpen) {
        focusFirstInputInContainer(employmentExtendedDetails);
      }
    });
  });

  if (businessLineSelect) businessLineSelect.addEventListener("change", syncPresentEmploymentSection);
  if (jobLevelSelect) jobLevelSelect.addEventListener("change", syncPresentEmploymentSection);

  if (!getCheckedRadioValue("shareMoreFirstJobDetails") && hasFirstJobExtendedValues()) {
    const firstJobMoreYes = document.getElementById("shareMoreFirstJobDetailsYes");
    if (firstJobMoreYes) firstJobMoreYes.checked = true;
  }

  if (!getCheckedRadioValue("shareMoreEmploymentDetails") && hasEmploymentExtendedValues()) {
    const employmentMoreYes = document.getElementById("shareMoreEmploymentDetailsYes");
    if (employmentMoreYes) employmentMoreYes.checked = true;
  }

  setFirstJobExtendedSaved(isFirstJobExtendedComplete());
  setEmploymentExtendedSaved(isEmploymentExtendedComplete());
  firstJobExtendedViewOpen = getCheckedRadioValue("shareMoreFirstJobDetails") === "Yes" && !firstJobExtendedViewSaved;
  employmentExtendedViewOpen = getCheckedRadioValue("shareMoreEmploymentDetails") === "Yes" && !employmentExtendedViewSaved;

  if (firstJobExtendedFields) {
    firstJobExtendedFields.querySelectorAll("input, select, textarea").forEach((input) => {
      input.addEventListener("input", function () {
        if (getCheckedRadioValue("shareMoreFirstJobDetails") === "Yes") {
          setFirstJobExtendedSaved(false);
          syncFirstJobSection();
        }
      });

      input.addEventListener("change", function () {
        if (getCheckedRadioValue("shareMoreFirstJobDetails") === "Yes") {
          setFirstJobExtendedSaved(false);
          syncFirstJobSection();
        }
      });
    });
  }

  if (employmentExtendedDetails) {
    employmentExtendedDetails.querySelectorAll("input, select, textarea").forEach((input) => {
      input.addEventListener("input", function () {
        if (getCheckedRadioValue("shareMoreEmploymentDetails") === "Yes") {
          setEmploymentExtendedSaved(false);
          syncPresentEmploymentSection();
        }
      });

      input.addEventListener("change", function () {
        if (getCheckedRadioValue("shareMoreEmploymentDetails") === "Yes") {
          setEmploymentExtendedSaved(false);
          syncPresentEmploymentSection();
        }
      });
    });
  }

  document.querySelectorAll('input[name="presentJobReasons[]"]').forEach((input) => {
    input.addEventListener("change", function () {
      syncOtherCheckboxInput("presentJobReasons[]", presentJobReasonsOtherContainer, presentJobReasonsOtherInput, getCheckedRadioValue("presentlyEmployed") === "Yes");
    });
  });

  document.querySelectorAll('input[name="hasEmployeeAwards"]').forEach((input) => {
    input.addEventListener("change", syncEmployeeAwards);
  });

  document.querySelectorAll('input[name="hasCurriculumSuggestion"]').forEach((input) => {
    input.addEventListener("change", function () {
      syncSuggestionToggle("hasCurriculumSuggestion", curriculumSuggestionsContainer, curriculumSuggestionsInput);
    });
  });

  document.querySelectorAll('input[name="hasEmployabilitySuggestion"]').forEach((input) => {
    input.addEventListener("change", function () {
      syncSuggestionToggle("hasEmployabilitySuggestion", employabilitySuggestionsContainer, employabilitySuggestionsInput);
    });
  });

  document.querySelectorAll('input[name="hasFacilitySuggestion"]').forEach((input) => {
    input.addEventListener("change", function () {
      syncSuggestionToggle("hasFacilitySuggestion", facilitySuggestionsContainer, facilitySuggestionsInput);
    });
  });

  if (firstJobExtendedFieldsClose) {
    firstJobExtendedFieldsClose.addEventListener("click", function () {
      if (hasFirstJobExtendedValues()) {
        setRadioGroupValue("shareMoreFirstJobDetails", "Yes");
      } else {
        clearRadioGroup("shareMoreFirstJobDetails");
        setFirstJobExtendedSaved(false);
      }

      firstJobExtendedViewOpen = false;
      syncFirstJobSection();
    });
  }

  if (firstJobExtendedFieldsDone) {
    firstJobExtendedFieldsDone.addEventListener("click", function () {
      const result = validateRequiredFields(firstJobExtendedFields);
      if (!result.ok) {
        if (result.firstInvalid) {
          try {
            result.firstInvalid.focus();
          } catch (error) {}
        }
        return;
      }

      setFirstJobExtendedSaved(true);
      firstJobExtendedViewOpen = false;
      syncFirstJobSection();
    });
  }

  if (firstJobExtendedFieldsReview) {
    firstJobExtendedFieldsReview.addEventListener("click", function () {
      firstJobExtendedViewOpen = true;
      syncFirstJobSection();
      focusFirstInputInContainer(firstJobExtendedFields);
    });
  }

  if (employmentExtendedDetailsClose) {
    employmentExtendedDetailsClose.addEventListener("click", function () {
      if (hasEmploymentExtendedValues()) {
        setRadioGroupValue("shareMoreEmploymentDetails", "Yes");
      } else {
        clearRadioGroup("shareMoreEmploymentDetails");
        setEmploymentExtendedSaved(false);
      }

      employmentExtendedViewOpen = false;
      syncPresentEmploymentSection();
    });
  }

  if (employmentExtendedDetailsDone) {
    employmentExtendedDetailsDone.addEventListener("click", function () {
      const result = validateRequiredFields(employmentExtendedDetails, [
        {
          name: "presentJobReasons[]",
          message: "Please select at least one reason for accepting the present job.",
        },
      ]);

      if (!result.ok) {
        if (result.firstInvalid) {
          try {
            result.firstInvalid.focus();
          } catch (error) {}
        }
        return;
      }

      setEmploymentExtendedSaved(true);
      employmentExtendedViewOpen = false;
      syncPresentEmploymentSection();
    });
  }

  if (employmentExtendedDetailsReview) {
    employmentExtendedDetailsReview.addEventListener("click", function () {
      employmentExtendedViewOpen = true;
      syncPresentEmploymentSection();
      focusFirstInputInContainer(employmentExtendedDetails);
    });
  }

  syncCurriculumSection();
  syncFirstJobSection();
  syncPresentEmploymentSection();
  syncSuggestionToggle("hasCurriculumSuggestion", curriculumSuggestionsContainer, curriculumSuggestionsInput);
  syncSuggestionToggle("hasEmployabilitySuggestion", employabilitySuggestionsContainer, employabilitySuggestionsInput);
  syncSuggestionToggle("hasFacilitySuggestion", facilitySuggestionsContainer, facilitySuggestionsInput);

  // ==================== FORM SUBMISSION ====================
  if (form) {
    function canUseEnterToAdvance(target) {
      if (!(target instanceof HTMLElement)) return true;
      if (target.closest("button, a, [role='button']")) return false;
      if (target.closest("[contenteditable='true']")) return false;
      if (target.tagName === "TEXTAREA") return false;

      if (target.tagName === "INPUT") {
        const inputType = (target.getAttribute("type") || "text").toLowerCase();
        return !["button", "submit", "reset", "file"].includes(inputType);
      }

      return true;
    }

    async function triggerCurrentSectionAction() {
      if (await handleAccountStepEnterAction()) {
        return;
      }

      const activeFieldsets = getFieldsetsForSection(currentStep);
      const lastVisibleFieldset = activeFieldsets[activeFieldsets.length - 1] || null;
      if (!lastVisibleFieldset) return;

      if (currentStep === totalSections - 1) {
        const submitBtn = lastVisibleFieldset.querySelector("#submitBtn") || document.getElementById("submitBtn");
        if (!submitBtn || submitBtn.disabled) return;

        if (typeof form.requestSubmit === "function") {
          form.requestSubmit(submitBtn);
          return;
        }

        submitBtn.click();
        return;
      }

      const nextBtn = lastVisibleFieldset.querySelector(".next-btn");
      if (!nextBtn || nextBtn.disabled) return;
      nextBtn.click();
    }

    // disable browser native validation (prevents "not focusable" errors
    // for hidden required controls); we use custom validateStep instead
    form.noValidate = true;
    // Allow Enter on text-entry controls to move the form forward like the visible action button.
    form.addEventListener("keydown", function (e) {
      if (e.key !== "Enter") return;
      if (e.isComposing || e.shiftKey || e.ctrlKey || e.metaKey || e.altKey) return;
      if (!canUseEnterToAdvance(e.target)) return;

      e.preventDefault();
      void triggerCurrentSectionAction();
    });
    const submitBtn = document.getElementById('submitBtn');

    function setRegistrationSubmitState(isSubmitting) {
      registrationSubmitInProgress = isSubmitting;

      if (submitBtn) {
        submitBtn.disabled = isSubmitting;
        submitBtn.textContent = isSubmitting ? "Submitting..." : "Submit";
      }

      if (submitAccountOverviewConfirmBtn) {
        submitAccountOverviewConfirmBtn.disabled = isSubmitting;
        submitAccountOverviewConfirmBtn.textContent = isSubmitting ? "Submitting..." : "Submit registration";
      }

      if (submitAccountOverviewBackBtn) {
        submitAccountOverviewBackBtn.disabled = isSubmitting;
      }

      if (submitAccountOverviewCloseBtn) {
        submitAccountOverviewCloseBtn.disabled = isSubmitting;
      }

      syncSubmitAccountOverviewPreview();
    }

    function showRegistrationServerErrors(errors, fallbackMessage) {
      const entries = Object.entries(errors || {});
      if (entries.length === 0) return false;

      const [fieldName, rawMessages] = entries[0];
      const message = Array.isArray(rawMessages)
        ? String(rawMessages[0] || fallbackMessage || "Please check this field.")
        : String(rawMessages || fallbackMessage || "Please check this field.");
      const baseName = fieldName.replace(/\.\d+$/, "");
      const candidateNames = new Set([fieldName, baseName, `${baseName}[]`]);
      const field = Array.from(form.elements).find((element) =>
        element instanceof HTMLElement
        && !element.disabled
        && (candidateNames.has(element.name) || candidateNames.has(element.id))
      );

      if (field) {
        const fieldset = field.closest("fieldset.form-step");
        const fieldsetIndex = fieldset ? fieldsets.indexOf(fieldset) : -1;
        if (fieldsetIndex >= 0) {
          showStep(getSectionIndexForFieldset(fieldsetIndex));
        }
        markInvalid(field, message);
        try { field.focus(); } catch (e) {}
        return true;
      }

      const currentFieldset = getFieldsetsForSection(currentStep)[0] || fieldsets[currentStep];
      if (!currentFieldset) return false;

      let messageElement = currentFieldset.querySelector("[data-registration-validation-message]");
      if (!messageElement) {
        messageElement = document.createElement("p");
        messageElement.dataset.registrationValidationMessage = "true";
        messageElement.className = "mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700";
        messageElement.setAttribute("role", "alert");
        const legend = currentFieldset.querySelector("legend");
        if (legend) {
          legend.insertAdjacentElement("afterend", messageElement);
        } else {
          currentFieldset.prepend(messageElement);
        }
      }
      messageElement.textContent = message;
      return true;
    }

    function validateRegistrationForSubmission() {
      for (let i = 0; i < fieldsets.length; i++) {
        const result = validateStep(i);
        if (!result.ok) {
          showStep(getSectionIndexForFieldset(i));
          if (result.firstInvalid) {
            try { result.firstInvalid.focus(); } catch (e) {}
          }
          submitAccountOverviewConfirmed = false;
          setSubmitAccountOverviewOpen(false);
          return false;
        }
      }

      if (emailInput && !isCurrentEmailVerified()) {
        const accountFieldsetIndex = fieldsets.findIndex((fieldset) => fieldset.contains(emailInput));
        showStep(getSectionIndexForFieldset(accountFieldsetIndex));
        markInvalid(emailInput, "Please verify your email address before completing registration.");
        syncEmailVerificationPanel();
        setEmailVerificationMessage("invalid", "Please verify your email address before completing registration.");
        try { emailInput.focus(); } catch (e) {}
        submitAccountOverviewConfirmed = false;
        setSubmitAccountOverviewOpen(false);
        return false;
      }

      return true;
    }

    function submitRegistrationRequest() {
      const action = form.getAttribute('action') || window.location.href;
      const method = (form.getAttribute('method') || 'POST').toUpperCase();
      const formData = new FormData(form);
      let serverValidationErrors = null;
      let serverResponseReceived = false;

      setRegistrationSubmitState(true);
      console.log('All steps valid â€” submitting form', { action, method });

      fetch(action, {
        method: method,
        body: formData,
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(async (res) => {
          serverResponseReceived = true;
          console.log('Registration response status', res.status, res.statusText);
          if (res.ok) {
            const data = await res.json().catch(() => ({}));
            window.location.href = data?.redirect || '/login?registered=1';
            return;
          }

          let message = 'Submission failed. Please try again or contact support.';
          try {
            const contentType = res.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
              const data = await res.json();
              if (data && data.message) message = data.message;
              if (data && data.errors) {
                serverValidationErrors = data.errors;
                const firstErrorKey = Object.keys(data.errors)[0];
                if (firstErrorKey && data.errors[firstErrorKey][0]) {
                  message = data.errors[firstErrorKey][0];
                }
              }
            } else {
              const text = await res.text();
              if (text && text.trim()) message = text;
            }
          } catch (e) {}

          throw new Error(message);
        })
        .catch((err) => {
          console.error('Registration submit failed', err);
          if (serverValidationErrors) {
            if (!showRegistrationServerErrors(serverValidationErrors, err?.message)) {
              showRegistrationServerErrors({ registration: err?.message }, err?.message);
            }
            return;
          }
          if (serverResponseReceived) {
            showRegistrationServerErrors({ registration: err?.message }, err?.message);
            return;
          }
          alert(err && err.message ? err.message : 'Submission failed. Please try again or contact support.');
        })
        .finally(() => {
          submitAccountOverviewConfirmed = false;
          setRegistrationSubmitState(false);
        });
    }
    // Ensure submit button is on top and receives pointer events
    if (submitBtn) {
      submitBtn.style.position = 'relative';
      submitBtn.style.zIndex = '9999';
      submitBtn.style.pointerEvents = 'auto';
    }

    form.addEventListener("input", function (event) {
      submitAccountOverviewConfirmed = false;

      if (
        !submitAccountOverviewModal
        || submitAccountOverviewModal.classList.contains("hidden")
      ) {
        return;
      }

      if (event.target === emailInput || event.target === passwordInput) {
        syncSubmitAccountOverviewPreview();
      }
    }, true);

    if (submitAccountOverviewPasswordToggle) {
      submitAccountOverviewPasswordToggle.addEventListener("click", function () {
        if (submitAccountOverviewPasswordToggle.disabled) return;

        submitAccountOverviewPasswordVisible = !submitAccountOverviewPasswordVisible;
        syncSubmitAccountOverviewPreview();
      });
    }

    [submitAccountOverviewCloseBtn, submitAccountOverviewBackBtn].forEach((button) => {
      if (!button) return;

      button.addEventListener("click", function () {
        if (registrationSubmitInProgress) return;

        submitAccountOverviewConfirmed = false;
        setSubmitAccountOverviewOpen(false);
      });
    });

    if (submitAccountOverviewConfirmBtn) {
      submitAccountOverviewConfirmBtn.addEventListener("click", function () {
        if (registrationSubmitInProgress) return;

        submitAccountOverviewConfirmed = true;
        setSubmitAccountOverviewOpen(false);

        if (typeof form.requestSubmit === "function" && submitBtn) {
          form.requestSubmit(submitBtn);
          return;
        }

        if (submitBtn) {
          submitBtn.click();
        }
      });
    }

    document.addEventListener("keydown", function (event) {
      if (event.key !== "Escape") return;
      if (!submitAccountOverviewModal || submitAccountOverviewModal.classList.contains("hidden")) return;
      if (registrationSubmitInProgress) return;

      event.preventDefault();
      submitAccountOverviewConfirmed = false;
      setSubmitAccountOverviewOpen(false);
    });

    form.addEventListener("submit", function (e) {
      e.preventDefault();

      if (registrationSubmitInProgress) {
        return;
      }

      if (!validateRegistrationForSubmission()) {
        return;
      }

      if (!submitAccountOverviewConfirmed && submitAccountOverviewModal) {
        setSubmitAccountOverviewOpen(true);
        return;
      }

      submitAccountOverviewConfirmed = false;
      submitRegistrationRequest();
      return;

      for (let i = 0; i < fieldsets.length; i++) {
        const result = validateStep(i);
        if (!result.ok) {
          showStep(getSectionIndexForFieldset(i));
          if (result.firstInvalid) {
            try { result.firstInvalid.focus(); } catch (e) {}
          }
          return;
        }
      }

      // Submit via fetch to capture success and redirect to login with message
      const action = form.getAttribute('action') || window.location.href;
      const method = (form.getAttribute('method') || 'POST').toUpperCase();
      const formData = new FormData(form);

      console.log('All steps valid — submitting form', { action, method });

      fetch(action, {
        method: method,
        body: formData,
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(async (res) => {
          console.log('Registration response status', res.status, res.statusText);
          if (res.ok) {
            // redirect to login page with query flag so it shows a message
            window.location.href = '/login?registered=1';
            return;
          }

          let message = 'Submission failed. Please try again or contact support.';
          try {
            const contentType = res.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
              const data = await res.json();
              if (data && data.message) message = data.message;
              if (data && data.errors) {
                const firstErrorKey = Object.keys(data.errors)[0];
                if (firstErrorKey && data.errors[firstErrorKey][0]) {
                  message = data.errors[firstErrorKey][0];
                }
              }
            } else {
              const text = await res.text();
              if (text && text.trim()) message = text;
            }
          } catch (e) {}

          throw new Error(message);
        })
        .catch((err) => {
          console.error('Registration submit failed', err);
          alert(err && err.message ? err.message : 'Submission failed. Please try again or contact support.');
        });
    });
  }

  // ==================== MASTER MAJOR OTHER HANDLING ====================
  const mastMajorSelectEl = document.getElementById("mastmajor");
  const otherMastMajorContainer = document.getElementById("otherMastMajorContainer");
  const otherMastMajorInput = document.getElementById("othermastMajor");

  if (mastMajorSelectEl && otherMastMajorContainer && otherMastMajorInput) {
    mastMajorSelectEl.addEventListener("change", function () {
      const shouldShow = this.value === "Other";
      otherMastMajorContainer.classList.toggle("hidden", !shouldShow);
      otherMastMajorInput.required = shouldShow;
      if (!shouldShow) otherMastMajorInput.value = "";
    });
    otherMastMajorContainer.classList.add("hidden");
    otherMastMajorInput.required = false;
  }

  // ==================== DOCTORATE MAJOR OTHER HANDLING ====================
  const doctMajorSelectEl = document.getElementById("doctmajor");
  const otherDoctMajorContainer = document.getElementById("otherDoctMajorContainer");
  const otherDoctMajorInput = document.getElementById("otherdoctMajor");

  if (doctMajorSelectEl && otherDoctMajorContainer && otherDoctMajorInput) {
    doctMajorSelectEl.addEventListener("change", function () {
      const shouldShow = this.value === "Other";
      otherDoctMajorContainer.classList.toggle("hidden", !shouldShow);
      otherDoctMajorInput.required = shouldShow;
      if (!shouldShow) otherDoctMajorInput.value = "";
    });
    otherDoctMajorContainer.classList.add("hidden");
    otherDoctMajorInput.required = false;
  }

  // ✅ Apply Tailwind styling after dynamic elements are created
  // Group label + control into a small wrapper so `space-y-6` on the
  // fieldset doesn't create a large gap between them.
  (function groupLabelAndControl() {
    document.querySelectorAll('fieldset.form-step').forEach((fs) => {
      const children = Array.from(fs.children);
      for (let i = 0; i < children.length - 1; i++) {
        const a = children[i];
        const b = children[i + 1];

        if (!a || !b) continue;
        if (a.tagName !== 'LABEL') continue;

        const controlTags = ['SELECT', 'INPUT', 'TEXTAREA', 'DIV'];
        if (!controlTags.includes(b.tagName)) continue;

        // skip if already grouped
        if (a.parentElement && a.parentElement.classList && a.parentElement.classList.contains('field-pair')) continue;

        const wrapper = document.createElement('div');
        wrapper.classList.add('field-pair', 'flex', 'flex-col', 'gap-1');

        fs.insertBefore(wrapper, a);
        wrapper.appendChild(a);
        wrapper.appendChild(b);

        // update the children array to avoid double-processing
        children.splice(i, 2, wrapper);
      }
    });
  })();

  applyTailwindToInputs();
  applyTailwindToButtons();
  syncCompactRadioGroupLayout();

  // ==================== REGISTRATION PASSWORD SHOW/HIDE ====================
  document.querySelectorAll(".registration-password-toggle").forEach((btn) => {
    btn.addEventListener("click", function () {
      const targetId = this.getAttribute("data-target");
      const input = document.getElementById(targetId);
      if (!input) return;

      const isHidden = input.type === "password";
      input.type = isHidden ? "text" : "password";
      this.textContent = isHidden ? "Hide" : "Show";
    });
  });

  if (emailInput) {
    emailInput.addEventListener("input", function () {
      const email = this.value.trim();
      emailCheckState.email = email;
      resetEmailVerificationState();

      if (emailCheckTimeoutId) clearTimeout(emailCheckTimeoutId);

      if (!email) {
        setEmailStatus("idle", "");
        syncEmailVerificationPanel();
        return;
      }

      if (!isValidEmailFormat(email)) {
        setEmailStatus("invalid", "Please enter a valid email format.");
        syncEmailVerificationPanel();
        return;
      }

      setEmailStatus("checking", "Checking email availability...");
      syncEmailVerificationPanel();
      emailCheckTimeoutId = setTimeout(() => {
        checkEmailAvailability(email);
      }, 350);
    });

    emailInput.addEventListener("blur", function () {
      const email = this.value.trim();
      if (!email) return;
      checkEmailAvailability(email);
    });
  }

  [sendEmailVerificationBtn, resendEmailVerificationBtn].forEach((button) => {
    if (!button) return;

    button.addEventListener("click", function () {
      sendEmailVerificationCode();
    });
  });

  emailVerificationCloseBtn?.addEventListener("click", dismissEmailVerificationMiniView);
  emailVerificationBackdrop?.addEventListener("click", dismissEmailVerificationMiniView);
  emailVerificationOpenBtn?.addEventListener("click", openEmailVerificationMiniView);

  document.addEventListener("keydown", function (event) {
    if (!emailVerificationMiniViewOpen || !emailVerificationPanel) return;

    if (event.key === "Escape") {
      event.preventDefault();
      dismissEmailVerificationMiniView();
      return;
    }

    if (event.key !== "Tab") return;

    const focusableElements = Array.from(emailVerificationPanel.querySelectorAll(
      'button:not(:disabled):not(.hidden), input:not(:disabled):not([type="hidden"])'
    )).filter((element) => element.offsetParent !== null);
    if (!focusableElements.length) return;

    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    if (event.shiftKey && (document.activeElement === firstElement || !emailVerificationPanel.contains(document.activeElement))) {
      event.preventDefault();
      lastElement.focus();
    } else if (!event.shiftKey && (document.activeElement === lastElement || !emailVerificationPanel.contains(document.activeElement))) {
      event.preventDefault();
      firstElement.focus();
    }
  });

  if (verifyEmailCodeBtn) {
    verifyEmailCodeBtn.addEventListener("click", function () {
      verifyEmailVerificationCode();
    });
  }

  if (emailVerificationCodeInput) {
    emailVerificationCodeInput.addEventListener("input", function () {
      this.value = this.value.replace(/\D/g, "").slice(0, 6);
      clearInvalid(this);
      syncEmailVerificationOtpDisplay();
      if (emailVerificationState.status === "invalid") {
        setEmailVerificationMessage("sent", "Enter the email code.");
      }
    });

    emailVerificationCodeInput.addEventListener("focus", syncEmailVerificationOtpDisplay);
    emailVerificationCodeInput.addEventListener("blur", syncEmailVerificationOtpDisplay);

    emailVerificationCodeInput.addEventListener("keydown", function (event) {
      if (event.key !== "Enter") return;

      event.preventDefault();
      event.stopPropagation();
      verifyEmailVerificationCode();
    });
  }

  syncEmailVerificationPanel();
  syncAccountPasswordGate();

  const verificationCollegeSelect = document.getElementById("collegeGraduated");
  if (verificationCollegeSelect && verificationDegreeSelect) {
    verificationCollegeSelect.addEventListener("change", function () {
      syncVerificationDegreeOptions();
      syncVerificationProgramLink({ college: this.value, degree: "" });
    });

    verificationDegreeSelect.addEventListener("change", function () {
      syncVerificationProgramLink({
        college: verificationCollegeSelect.value,
        degree: this.value,
      });
    });

    syncVerificationDegreeOptions(verificationDegreeSelect.value);
  }

  verificationFieldNames.forEach((fieldName) => {
    const field = document.querySelector(`[name="${fieldName}"]`);
    if (!field) return;

    if (fieldName === "middlename") {
      const normalizeMiddleName = function () {
        const cleaned = (this.value || "")
          .replace(/[^A-Za-z\s.'-]/g, "")
          .replace(/\s+/g, " ")
          .trimStart();

        this.value = cleaned;
      };

      field.addEventListener("input", function () {
        normalizeMiddleName.call(this);
        if (!getMiddleNameValidationMessage(this.value)) {
          clearInvalid(this);
        }
      });
      field.addEventListener("blur", function () {
        normalizeMiddleName.call(this);
        this.value = (this.value || "").trim();

        const message = getMiddleNameValidationMessage(this.value);
        if (message) {
          markInvalid(this, message);
        } else {
          clearInvalid(this);
        }
      });
    }

    const resetHandler = function () {
      resetVerificationState();
      queueLiveVerification();
    };

    field.addEventListener("input", resetHandler);
    field.addEventListener("change", resetHandler);
    field.addEventListener("blur", function () {
      queueLiveVerification(0);
    });
  });

  syncVerificationIdleMessage();

  // ==================== PASSWORD HINT VISIBILITY ====================
  if (passwordInput && passwordHint) {
    const updatePasswordLiveState = function () {
      const value = passwordInput.value || "";

      if (!value.length) {
        clearInvalid(passwordInput);
        passwordHint.textContent = "Must be at least 8 characters.";
        passwordHint.classList.remove("text-red-600", "font-medium");
        passwordHint.classList.add("text-gray-500");
        return;
      }

      if (value.length < 8) {
        markInvalid(passwordInput, "Password must be at least 8 characters.");
        passwordHint.textContent = "Password must be at least 8 characters.";
        passwordHint.classList.remove("text-gray-500");
        passwordHint.classList.add("text-red-600", "font-medium");
      } else {
        clearInvalid(passwordInput);
        passwordHint.textContent = "Looks good. Keep going.";
        passwordHint.classList.remove("text-red-600", "font-medium");
        passwordHint.classList.add("text-gray-500");
      }
    };

    passwordInput.addEventListener("focus", function () {
      passwordHint.classList.remove("hidden");
      updatePasswordLiveState();
    });
    passwordInput.addEventListener("blur", function () {
      passwordHint.classList.add("hidden");
      updatePasswordLiveState();
    });
    passwordInput.addEventListener("input", function () {
      updatePasswordLiveState();
    });
  }

  if (passwordInput && passwordConfirmationInput) {
    const updatePasswordMatchState = function () {
      const passwordValue = passwordInput.value || "";
      const confirmationValue = passwordConfirmationInput.value || "";

      if (!confirmationValue.length) {
        clearInvalid(passwordConfirmationInput);
        return;
      }

      if (passwordValue !== confirmationValue) {
        markInvalid(passwordConfirmationInput, "Password confirmation does not match.");
      } else {
        clearInvalid(passwordConfirmationInput);
      }
    };

    passwordConfirmationInput.addEventListener("input", updatePasswordMatchState);
    passwordInput.addEventListener("input", updatePasswordMatchState);
  }

  setupLiveRequiredValidation();

});
