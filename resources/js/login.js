const initializeLoginForm = () => {
  const form = document.querySelector("#loginForm");
  const emailInput = form?.querySelector('input[name="email"]');
  const passwordInput = form?.querySelector('input[name="password"]');
  const passwordToggle = document.querySelector("#loginPasswordToggle");
  const submitBtn = form?.querySelector(".submit-btn");

  if (!form || form.dataset.loginInitialized === "true") return;
  form.dataset.loginInitialized = "true";

  const addFocusStyle = (input) => {
    if (!input) return;

    input.classList.add("ring-2", "ring-[#1c4000]", "border-[#1c4000]");
    input.classList.remove("border-gray-300");
  };

  const removeFocusStyle = (input) => {
    if (!input) return;

    input.classList.remove("ring-2", "ring-[#1c4000]", "border-[#1c4000]");
    input.classList.add("border-gray-300");
  };

  [emailInput, passwordInput].forEach((input) => {
    if (!input) return;

    input.addEventListener("focus", () => addFocusStyle(input));
    input.addEventListener("blur", () => removeFocusStyle(input));
  });

  if (passwordInput && passwordToggle) {
    passwordToggle.addEventListener("click", () => {
      const isHidden = passwordInput.type === "password";
      const toggleIcon = passwordToggle.querySelector("i");
      const selectionStart = passwordInput.selectionStart;
      const selectionEnd = passwordInput.selectionEnd;

      passwordInput.type = isHidden ? "text" : "password";
      passwordToggle.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
      passwordToggle.setAttribute("aria-pressed", isHidden ? "true" : "false");

      if (toggleIcon) {
        toggleIcon.classList.toggle("bx-show", !isHidden);
        toggleIcon.classList.toggle("bx-hide", isHidden);
      }

      passwordToggle.classList.toggle("text-[#003300]", isHidden);
      passwordInput.focus({ preventScroll: true });

      if (selectionStart !== null && selectionEnd !== null) {
        try {
          passwordInput.setSelectionRange(selectionStart, selectionEnd);
        } catch {
          // Some mobile browsers do not support selection ranges for password fields.
        }
      }
    });
  }

  if (form) {
    form.addEventListener("submit", () => {
      if (emailInput) emailInput.value = emailInput.value.trim();
      if (!submitBtn) return;

      submitBtn.disabled = true;
      submitBtn.classList.add("opacity-70", "cursor-not-allowed");

      const submitLabel = submitBtn.querySelector("span");
      if (submitLabel) {
        submitLabel.innerText = "Signing in...";
      } else {
        submitBtn.innerText = "Signing in...";
      }
    });
  }
};

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initializeLoginForm, { once: true });
} else {
  initializeLoginForm();
}
