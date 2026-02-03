console.log("registration.js loaded");

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
        accent-color: #16a34a !important; /* green-600 when checked */
      }

      /* ensure unchecked controls aren't showing browser fill */
      input[type="checkbox"]:not(:checked), input[type="radio"]:not(:checked) {
        background-image: none !important;
      }
    `;
    const style = document.createElement('style');
    style.appendChild(document.createTextNode(css));
    document.head.appendChild(style);
  })();

  // ==================== MULTI-STEP FORM ====================
  const fieldsets = document.querySelectorAll("fieldset");
  let currentStep = 0;

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

  // ✅ Show step using Tailwind (hidden/block)
  function showStep(step) {
    const totalSteps = fieldsets.length;

    fieldsets.forEach((fs, index) => {
      if (index === step) {
        fs.classList.remove("hidden");
        fs.classList.add("block");
      } else {
        fs.classList.remove("block");
        fs.classList.add("hidden");
      }
    });

    // Update progress UI: step text, step title (legend), and progress bar
    const stepTextEl = document.getElementById("stepText");
    if (stepTextEl) stepTextEl.textContent = `Step ${step + 1} of ${totalSteps}`;

    const stepTitleEl = document.getElementById("stepTitle");
    const legend = fieldsets[step].querySelector("legend");
    if (stepTitleEl) stepTitleEl.textContent = legend ? legend.textContent : "";

    const progressBar = document.getElementById("progressBar");
    if (progressBar) {
      const percent = Math.round(((step + 1) / totalSteps) * 100);
      progressBar.style.width = `${percent}%`;
    }

    // Accessibility: Focus first input
    const firstInput = fieldsets[step].querySelector("input, select, textarea");
    if (firstInput) firstInput.focus();
  }

  // Hide all fieldsets first (Tailwind way)
  fieldsets.forEach((fs) => {
    fs.classList.add("hidden");
  });

  showStep(currentStep);

  // ==================== VALIDATION ====================
  function markInvalid(input) {
    input.classList.remove("border-gray-300");
    input.classList.add("border-red-500", "ring-2", "ring-red-200");
  }

  function clearInvalid(input) {
    input.classList.remove("border-red-500", "ring-2", "ring-red-200");
    input.classList.add("border-gray-300");
  }

  function validateStep(step) {
    const currentFieldset = fieldsets[step];
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
          alert("Please complete all required fields.");
          return false;
        }
      } else if (!input.value || !input.value.trim()) {
        console.warn("Validation failed — missing required field:", input.id || input.name || input);
        alert("Please fill out all required fields.");
        markInvalid(input);
        try { input.focus(); } catch (e) {}
        return false;
      }
    }

    // Special validation for Professional Exams: if this fieldset contains
    // any `proExam[]` checkboxes require at least one to be selected.
    const proExamInputs = currentFieldset.querySelectorAll('input[name="proExam[]"]');
    if (proExamInputs.length > 0) {
      const checkedBoxes = currentFieldset.querySelectorAll('input[name="proExam[]"]:checked');
      if (checkedBoxes.length === 0) {
        alert("Please select at least one professional examination.");
        return false;
      }
    }

    return true;
  }

  // ==================== HISTORY STATE FOR FORM STEPS ====================
  function pushStepToHistory(step) {
    window.history.pushState({ step: step }, null, "");
  }

  window.history.replaceState({ step: currentStep }, null, "");

  window.addEventListener("popstate", function (event) {
    if (event.state && typeof event.state.step !== "undefined") {
      currentStep = event.state.step;
      showStep(currentStep);
      window.scrollTo(0, 0);
    } else {
      currentStep = 0;
      showStep(currentStep);
      window.history.replaceState({ step: currentStep }, null, "");
    }
  });

  // ==================== NEXT / PREVIOUS BUTTONS ====================
  document.querySelectorAll(".next-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      if (!validateStep(currentStep)) return;

      if (currentStep < fieldsets.length - 1) {
        currentStep++;
        showStep(currentStep);
        window.scrollTo(0, 0);
        pushStepToHistory(currentStep);
      }
    });
  });

  document.querySelectorAll(".previous-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      if (currentStep > 0) {
        currentStep--;
        showStep(currentStep);
        window.scrollTo(0, 0);
      }
    });
  });

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
  populateDropdown(
    "department",
    [
      "College of Agriculture",
      "College of Arts and Sciences",
      "College of Education",
      "College of Veterinary Medicine",
      "College of Engineering",
      "College of Forestry and Environmental Science",
      "College of Business and Management",
      "College of Human Ecology",
      "College of Nursing",
      "College of Information Sciences and Computing",
    ],
    "Select Department"
  );

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
    container.classList.add('mb-2');

    const qLabel = document.createElement('label');
    qLabel.innerText = 'Are you a CMU Laboratory High School alumnus/alumna?';

    const row = document.createElement('div');
    row.classList.add('flex', 'items-center', 'gap-4', 'mt-2');

    const yes = document.createElement('input');
    yes.type = 'radio';
    yes.name = 'isCMULHS';
    yes.id = 'isCMULHSYes';
    yes.value = 'Yes';
    yes.required = true;

    const yesLabel = document.createElement('label');
    yesLabel.htmlFor = 'isCMULHSYes';
    yesLabel.innerText = 'Yes';

    const no = document.createElement('input');
    no.type = 'radio';
    no.name = 'isCMULHS';
    no.id = 'isCMULHSNo';
    no.value = 'No';
    no.required = true;

    const noLabel = document.createElement('label');
    noLabel.htmlFor = 'isCMULHSNo';
    noLabel.innerText = 'No';

    row.append(yes, yesLabel, no, noLabel);

    // Create CMULHS-specific dropdown (will reuse highschoolSelect but limit options)
    const cmuSelect = document.createElement('select');
    cmuSelect.id = 'cmuHighschoolSelect';
    cmuSelect.name = 'cmuHighschoolSelect';
    cmuSelect.classList.add('hidden', 'mt-1');

    ['Select Program', 'CMULHS - Senior Highschool Graduated', 'CMULHS - Junior Highschool Graduated', 'CMU Laboratory High School'].forEach(opt => {
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

  populateDropdown("degree", [
    "Bachelor of Science in Agriculture",
    "Bachelor of Science in Development Communication",
    "Bachelor of Arts in English",
    "Bachelor of Arts in History",
    "Bachelor of Arts in Political Science",
    "Bachelor of Arts in Psychology",
    "Bachelor of Arts in Sociology",
    "Bachelor of Science in Biology",
    "Bachelor of Science in Chemistry",
    "Bachelor of Science in Mathematics",
    "Bachelor of Science in Forestry",
    "Bachelor of Science in Environmental Science",
    "Bachelor of Science in Business Administration",
    "Bachelor of Science in Accountancy",
    "Bachelor of Science in Accounting Technology",
    "Bachelor of Science in Office Administration",
    "Bachelor of Science in Education",
    "Bachelor of Science in Home Economics Education",
    "Bachelor of Science in Food Technology",
    "Bachelor of Science in Hotel and Restaurant Management",
    "Bachelor of Science in Nutrition and Dietetics",
    "Bachelor of Science in Nursing",
  ]);

  populateDropdown("major", [
    "Major in Agronomy",
    "Major in Agricultural Economics",
    "Major in Agricultural Education",
    "Major in Agricultural Extension",
    "Major in Animal Science",
    "Major in Entomology",
    "Major in Horticulture",
    "Major in Plant Pathology",
    "Major in Soil Science",
    "Major in Marketing Management",
    "Major in Operations Management",
    "Major in Financial Management",
    "Major in Biology",
    "Major in English",
    "Major in Filipino",
    "Major in General Science",
    "Major in Mathematics",
    "Major in Physical Education",
    "Major in History",
    "Major in Political Science",
    "Major in Psychology",
    "Major in Sociology",
    "Major in Software Development",
    "Major in Information Management",
    "Major in Data Networking",
    "N/A",
  ]);

  populateDropdown(
    "nondegree",
    [
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
    ],
    "Select Program"
  );

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
    "N/A",
    "Other",
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

      wrapper.appendChild(label);
      wrapper.appendChild(checkbox);
      proExamContainer.appendChild(wrapper);

      if (exam === "Other") {
        checkbox.addEventListener("change", function () {
          if (!proExamOtherContainer) return;
          proExamOtherContainer.classList.toggle("hidden", !this.checked);

          const otherInput = document.getElementById("proExamOther");
          if (!this.checked && otherInput) otherInput.value = "";
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

  // ==================== SCHOLARSHIP DATA UI ====================
  (function setupScholarshipUI() {
    const scholarshipContainer = document.getElementById("scholarshipContainer");
    if (!scholarshipContainer) return;

    const questionLabel = document.createElement("label");
    questionLabel.innerText = "Did you receive a scholarship?";

    const radioRow = document.createElement("div");
    radioRow.classList.add("flex", "items-center", "gap-6", "mt-2");

    const yesWrapper = document.createElement("div");
    yesWrapper.classList.add("flex", "items-center", "gap-2");
    const yesRadio = document.createElement("input");
    yesRadio.type = "radio";
    yesRadio.name = "hasScholarship";
    yesRadio.id = "scholarYes";
    yesRadio.value = "Yes";
    const yesLabel = document.createElement("label");
    yesLabel.htmlFor = "scholarYes";
    yesLabel.innerText = "Yes";
    yesLabel.classList.add("text-sm", "text-gray-700");
    yesWrapper.append(yesRadio, yesLabel);

    const noWrapper = document.createElement("div");
    noWrapper.classList.add("flex", "items-center", "gap-2");
    const noRadio = document.createElement("input");
    noRadio.type = "radio";
    noRadio.name = "hasScholarship";
    noRadio.id = "scholarNo";
    noRadio.value = "No";
    const noLabel = document.createElement("label");
    noLabel.htmlFor = "scholarNo";
    noLabel.innerText = "No";
    noLabel.classList.add("text-sm", "text-gray-700");
    noWrapper.append(noRadio, noLabel);

    radioRow.append(yesWrapper, noWrapper);

    const detailsDiv = document.createElement("div");
    detailsDiv.classList.add("hidden", "mt-3");

    const typeLabel = document.createElement("label");
    typeLabel.innerText = "Type of Scholarship:";

    const typeSelect = document.createElement("select");
    typeSelect.id = "scholarshipType";
    typeSelect.name = "scholarshipType";

    ["", "Full Scholarship", "Partial Scholarship", "Merit-based", "Need-based", "Other", "N/A"].forEach((opt) => {
      typeSelect.add(new Option(opt === "" ? "Select Type" : opt, opt));
    });

    const otherInput = document.createElement("input");
    otherInput.type = "text";
    otherInput.id = "scholarshipOther";
    otherInput.name = "scholarshipOther";
    otherInput.placeholder = "If Other, specify";
    otherInput.classList.add("hidden", "mt-2");

    const yearLabel = document.createElement("label");
    yearLabel.innerText = "Year Awarded:";

    const yearSelect = document.createElement("select");
    yearSelect.id = "scholarshipYear";
    yearSelect.name = "scholarshipYear";

    detailsDiv.append(typeLabel, typeSelect, otherInput, yearLabel, yearSelect);

    scholarshipContainer.append(questionLabel, radioRow, detailsDiv);

    [yesRadio, noRadio].forEach((r) => {
      r.addEventListener("change", function () {
        if (this.value === "Yes") {
          detailsDiv.classList.remove("hidden");
          typeSelect.required = true;
          yearSelect.required = true;
        } else {
          detailsDiv.classList.add("hidden");
          typeSelect.required = false;
          yearSelect.required = false;
          typeSelect.value = "";
          otherInput.value = "";
          otherInput.classList.add("hidden");
        }
      });
    });

    typeSelect.addEventListener("change", function () {
      if (this.value === "Other") {
        otherInput.classList.remove("hidden");
        otherInput.required = true;
      } else {
        otherInput.classList.add("hidden");
        otherInput.required = false;
        otherInput.value = "";
      }
    });

    populateYearDropdown("scholarshipYear");
  })();

  // ==================== MASTER DEGREE → MAJOR MAP ====================
  const masterDegreeToMajors = {
    "Master of Science in Agriculture": [
      "Agricultural Economics",
      "Agricultural Education",
      "Agricultural Extension",
      "Agronomy",
      "Animal Science",
      "Horticulture",
      "Soil Science",
      "Plant Pathology",
      "Plant Breeding",
      "Agribusiness Management",
      "Development Communication",
    ],
    "Master of Science in Biology": ["Biology"],
    "Master of Science in Chemistry": ["Chemistry"],
    "Master of Science in Mathematics": ["Mathematics"],
    "Master of Arts in Education": [
      "Educational Management",
      "Curriculum and Instruction",
      "Guidance and Counseling",
    ],
    "Master of Arts in Filipino": ["Filipino Language", "Panitikan"],
    "Master of Arts in History": ["History"],
    "Master of Arts in Political Science": ["Political Science"],
    "Master of Business Administration": [
      "Marketing Management",
      "Operations Management",
      "Financial Management",
    ],
    "Master of Public Administration": ["Public Administration"],
    "Master of General Science Education": ["General Science Education"],
    "Master of Engineering": [
      "Civil Engineering",
      "Electrical Engineering",
      "Mechanical Engineering",
      "Agricultural Engineering",
    ],
    "Master of Science in Environmental Management": ["Environmental Management"],
    "Master of Science in Forestry": ["Forestry"],
    "Master of Science in Food Service": ["Food Service Management"],
    "Master of Arts Home Economics Education": ["Home Economics Education"],
    "Master of Veterinary Medicine": [
      "Veterinary Medicine (Research)",
      "Veterinary Medicine (Academics)",
    ],
    "Master of Science in Information Technology": [
      "Information Technology",
      "Software Engineering",
      "Data Analytics",
    ],
    "N/A": ["N/A"],
  };

  const masterSelect = document.getElementById("master");
  const mastMajorSelect = document.getElementById("mastmajor");

  if (masterSelect && mastMajorSelect) {
    Object.keys(masterDegreeToMajors).forEach((degree) => {
      masterSelect.add(new Option(degree, degree));
    });

    mastMajorSelect.disabled = true;

    masterSelect.addEventListener("change", function () {
      const selectedDegree = this.value;
      mastMajorSelect.options.length = 0;

      if (!selectedDegree || selectedDegree === "N/A") {
        mastMajorSelect.add(new Option("N/A", "N/A"));
        mastMajorSelect.disabled = false;
        return;
      }

      mastMajorSelect.add(new Option("Select Major", ""));
      masterDegreeToMajors[selectedDegree].forEach((major) => {
        mastMajorSelect.add(new Option(major, major));
      });

      mastMajorSelect.disabled = false;
    });
  }

  // ==================== DOCTORATE DEGREE → MAJOR MAP ====================
  const doctorateDegreeToMajors = {
    "Doctor of Philosophy in Agricultural Economics": [
      "Animal Breeding and Physiology",
      "Animal Nutrition",
      "Chemistry",
      "Education",
      "Business Administration",
      "Information Technology",
    ],
    "Doctor of Philosophy in Agricultural Extension": [
      "Animal Nutrition",
      "Agricultural Extension",
    ],
    "Doctor of Philosophy in Agronomy": ["Agronomy"],
    "Doctor of Philosophy in Animal Science": ["Animal Science"],
    "Doctor of Philosophy in Entomology": ["Entomology"],
    "Doctor of Philosophy in Horticulture": ["Horticulture"],
    "Doctor of Philosophy in Plant Pathology": ["Plant Pathology"],
    "Doctor of Philosophy in Soil Science": ["Soil Science"],
    "Doctor of Philosophy in Biology": ["Biology"],
    "Doctor of Philosophy in Chemistry": ["Chemistry"],
    "Doctor of Philosophy in Mathematical Science": ["Mathematical Science"],
    "Doctor of Philosophy in Mathematics": ["Mathematics"],
    "Doctor of Philosophy in Educational Administration": [
      "Educational Management",
      "Curriculum and Instruction",
      "Guidance and Counseling",
    ],
    "Doctor of Philosophy in Language Education": ["English Language", "Literature"],
    "Doctor of Philosophy in Science Education": ["Science Education"],
    "Doctor of Education": ["Educational Management", "Curriculum and Instruction"],
    "Doctor of Public Administration": ["Public Administration"],
    "Doctor of Business Administration": ["Business Administration"],
    "Doctor of Veterinary Medicine": ["Veterinary Medicine"],
    "N/A": ["N/A"],
  };

  const doctorateSelect = document.getElementById("doctorate");
  const doctMajorSelect = document.getElementById("doctmajor");

  if (doctorateSelect && doctMajorSelect) {
    Object.keys(doctorateDegreeToMajors).forEach((degree) => {
      doctorateSelect.add(new Option(degree, degree));
    });

    doctMajorSelect.disabled = true;

    doctorateSelect.addEventListener("change", function () {
      const selectedDegree = this.value;
      doctMajorSelect.options.length = 0;

      if (!selectedDegree || selectedDegree === "N/A") {
        doctMajorSelect.add(new Option("N/A", "N/A"));
        doctMajorSelect.disabled = false;
        return;
      }

      doctMajorSelect.add(new Option("Select Major", ""));
      doctorateDegreeToMajors[selectedDegree].forEach((major) => {
        doctMajorSelect.add(new Option(major, major));
      });

      doctMajorSelect.disabled = false;
    });
  }

  // ==================== COUNTRY OTHER HANDLING ====================
  const countrySelect = document.getElementById("country");
  const countryOtherContainer = document.getElementById("countryOtherContainer");

  if (countrySelect && countryOtherContainer) {
    countrySelect.addEventListener("change", function () {
      countryOtherContainer.classList.toggle("hidden", this.value !== "Other");

      const otherInput = document.getElementById("countryOther");
      if (this.value !== "Other" && otherInput) otherInput.value = "";
    });
  }

  // ==================== NON-DEGREE OTHER HANDLING ====================
  const nondegreeSelect = document.getElementById("nondegree");
  const nondegreeOtherContainer = document.getElementById("nondegreeOtherContainer");

  if (nondegreeSelect && nondegreeOtherContainer) {
    nondegreeSelect.addEventListener("change", function () {
      nondegreeOtherContainer.classList.toggle("hidden", this.value !== "Other");

      const otherInput = document.getElementById("nondegreeOther");
      if (this.value !== "Other" && otherInput) otherInput.value = "";
    });
  }

  // ==================== MASTER OTHER HANDLING ====================
  const masterOtherContainer = document.getElementById("masterOtherContainer");

  if (masterSelect && masterOtherContainer) {
    masterSelect.addEventListener("change", function () {
      masterOtherContainer.classList.toggle("hidden", this.value !== "Other");

      const otherInput = document.getElementById("othermaster");
      if (this.value !== "Other" && otherInput) otherInput.value = "";
    });
  }

  // ==================== DOCTORATE OTHER HANDLING ====================
  const doctorateOtherContainer = document.getElementById("doctorateOtherContainer");

  if (doctorateSelect && doctorateOtherContainer) {
    doctorateSelect.addEventListener("change", function () {
      doctorateOtherContainer.classList.toggle("hidden", this.value !== "Other");

      const otherInput = document.getElementById("otherdoctorate");
      if (this.value !== "Other" && otherInput) otherInput.value = "";
    });
  }

  // ==================== EMPLOYMENT DATA OPTIONS ====================
  const competencies = [
    "Communication skills",
    "Human relations skills",
    "Entrepreneurial skills",
    "IT skills",
    "Problem-solving skills",
    "Critical thinking skills",
    "Leadership skills",
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
    "Job Fair of PESO",
    "N/A",
    "Other",
  ];

  const firstJobEarningsOptions = [
    "Below P5,000.00",
    "P5,000.00 to less than 10,000.00",
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

  populateDropdown("firstJobTime", firstJobTimeOptions, "Select Time");
  populateDropdown("firstJobRelated", firstJobRelatedOptions, "Select Option");
  populateDropdown("firstJobHow", firstJobHowOptions, "Select Option");
  populateDropdown("firstJobEarnings", firstJobEarningsOptions, "Select Option");
  populateDropdown("firstJobDuration", firstJobDurationOptions, "Select Duration");

  const competenciesSelect = document.getElementById("competenciesSelect");
  const competenciesOther = document.getElementById("competenciesOther");

  if (competenciesSelect) {
    competencies.forEach((skill) => {
      competenciesSelect.add(new Option(skill, skill));
    });

    competenciesSelect.addEventListener("change", function () {
      if (!competenciesOther) return;

      if (this.value === "Other") {
        competenciesOther.classList.remove("hidden");
        competenciesOther.required = true;
      } else {
        competenciesOther.classList.add("hidden");
        competenciesOther.value = "";
        competenciesOther.required = false;
      }
    });
  }

  // ==================== EMPLOYED AFTER GRADUATION LOGIC ====================
  const employedYes = document.getElementById("employedYes");
  const employedNo = document.getElementById("employedNo");

  let lastEmployedChecked = null;

  [employedYes, employedNo].forEach((radio) => {
    if (!radio) return;

    radio.addEventListener("click", function (e) {
      if (lastEmployedChecked === this) {
        e.preventDefault();
        this.checked = false;
        lastEmployedChecked = null;

        ["firstJobTime", "firstJobRelated", "firstJobHow", "firstJobEarnings", "firstJobDuration"].forEach((id) => {
          const el = document.getElementById(id);
          if (el) el.required = false;
        });

        return;
      }

      lastEmployedChecked = this;

      if (this.value === "Yes") {
        ["firstJobTime", "firstJobRelated", "firstJobHow", "firstJobEarnings", "firstJobDuration"].forEach((id) => {
          const el = document.getElementById(id);
          if (el) el.required = true;
        });
      } else {
        ["firstJobTime", "firstJobRelated", "firstJobHow", "firstJobEarnings", "firstJobDuration"].forEach((id) => {
          const el = document.getElementById(id);
          if (el) el.required = false;
        });
      }
    });
  });

  // ==================== EMPLOYMENT OTHER / N/A HANDLING ====================
  const employmentFields = [
    "firstJobTime",
    "firstJobRelated",
    "firstJobHow",
    "firstJobEarnings",
    "firstJobDuration",
  ];

  employmentFields.forEach((id) => {
    const select = document.getElementById(id);
    if (!select) return;

    const specifyContainer = document.createElement("div");
    specifyContainer.classList.add("hidden", "mt-2");

    const specifyLabel = document.createElement("label");
    specifyLabel.innerText = "Please explain / specify:";
    specifyLabel.htmlFor = `${id}Specify`;
    specifyLabel.classList.add("text-sm", "text-gray-700", "font-medium");

    const specifyInput = document.createElement("input");
    specifyInput.type = "text";
    specifyInput.id = `${id}Specify`;
    specifyInput.name = `${id}Specify`;
    specifyInput.placeholder = "Please explain";

    // Tailwind input style
    specifyInput.classList.add(
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

    specifyContainer.append(specifyLabel, specifyInput);
    select.parentNode.insertBefore(specifyContainer, select.nextSibling);

    select.addEventListener("change", function () {
      if (this.value === "Other" || this.value === "N/A") {
        specifyContainer.classList.remove("hidden");
        specifyInput.required = true;
      } else {
        specifyContainer.classList.add("hidden");
        specifyInput.value = "";
        specifyInput.required = false;
      }
    });
  });

  // ==================== FORM SUBMISSION ====================
  const form = document.getElementById("multiStepForm");
  if (form) {
    // disable browser native validation (prevents "not focusable" errors
    // for hidden required controls); we use custom validateStep instead
    form.noValidate = true;
    // Debug: log submit button clicks
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) submitBtn.addEventListener('click', () => console.log('Submit button clicked'));
    // Ensure submit button is on top and receives pointer events
    if (submitBtn) {
      submitBtn.style.position = 'relative';
      submitBtn.style.zIndex = '9999';
      submitBtn.style.pointerEvents = 'auto';
    }

    form.addEventListener("submit", function (e) {
      e.preventDefault();

      for (let i = 0; i < fieldsets.length; i++) {
        if (!validateStep(i)) {
          showStep(i);

          const firstInvalid = fieldsets[i].querySelector(
            "input:invalid, select:invalid, textarea:invalid"
          );
          if (firstInvalid) firstInvalid.focus();
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
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then((res) => {
          console.log('Registration response status', res.status, res.statusText);
          if (res.ok) {
            // redirect to login page with query flag so it shows a message
            window.location.href = '/login?registered=1';
          } else {
            return res.text().then((text) => { throw new Error(text || 'Submission failed'); });
          }
        })
        .catch((err) => {
          console.error('Registration submit failed', err);
          alert('Submission failed. Please try again or contact support.');
        });
    });
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

});
