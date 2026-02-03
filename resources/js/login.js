document.addEventListener("DOMContentLoaded", () => {
  console.log("login.js loaded ✅");

  // ==============================
  // ELEMENTS
  // ==============================
  const form = document.querySelector("#loginForm");
  const emailInput = document.querySelector('input[name="email"]');
  const passwordInput = document.querySelector('input[name="password"]');
  const submitBtn = document.querySelector(".submit-btn");

  // ==============================
  // INPUT FOCUS STYLE (Tailwind toggle)
  // ==============================
  const addFocusStyle = (input) => {
    if (!input) return;

    input.classList.add(
      "ring-2",
      "ring-[#1c4000]",
      "border-[#1c4000]"
    );

    input.classList.remove("border-gray-300");
  };

  const removeFocusStyle = (input) => {
    if (!input) return;

    input.classList.remove(
      "ring-2",
      "ring-[#1c4000]",
      "border-[#1c4000]"
    );

    input.classList.add("border-gray-300");
  };

  // Attach focus/blur listeners
  [emailInput, passwordInput].forEach((input) => {
    if (!input) return;

    input.addEventListener("focus", () => addFocusStyle(input));
    input.addEventListener("blur", () => removeFocusStyle(input));
  });

  // ==============================
  // SHOW/HIDE PASSWORD (Tailwind toggle)
  // ==============================
  // Auto-create toggle button if not existing
  if (passwordInput) {
    // Wrap password input with relative container if needed
    const parent = passwordInput.parentElement;

    if (parent && !parent.classList.contains("relative")) {
      parent.classList.add("relative");
    }

    // Create toggle button
    const toggleBtn = document.createElement("button");
    toggleBtn.type = "button";
    toggleBtn.innerText = "Show";

    toggleBtn.className =
      "absolute right-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-[#1c4000] hover:underline";

    parent.appendChild(toggleBtn);

    toggleBtn.addEventListener("click", () => {
      const isHidden = passwordInput.type === "password";

      passwordInput.type = isHidden ? "text" : "password";
      toggleBtn.innerText = isHidden ? "Hide" : "Show";

      // Tailwind visual feedback
      toggleBtn.classList.toggle("text-[#003300]");
    });
  }

  // ==============================
  // FORM SUBMIT LOADING STATE
  // ==============================
  if (form) {
    form.addEventListener("submit", () => {
      if (!submitBtn) return;

      // Disable button
      submitBtn.disabled = true;

      // Tailwind loading styles
      submitBtn.classList.add("opacity-70", "cursor-not-allowed");
      submitBtn.classList.remove("hover:bg-[#003300]");

      // Change text
      submitBtn.innerText = "Signing in...";
    });
  }
});
