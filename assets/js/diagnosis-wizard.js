/**
 * NetworkTrouble - Interactive Diagnosis Wizard (Tahap 3)
 * Multi-step navigation, dynamic adaptive questions, symptom filtering, and keyword detection.
 */

document.addEventListener('DOMContentLoaded', () => {
    let currentStep = 1;
    const totalSteps = 4;

    const stepItems = document.querySelectorAll('.wizard-step-indicator .step-item');
    const stepPanels = document.querySelectorAll('.wizard-step-panel');
    const btnPrev = document.getElementById('btnWizardPrev');
    const btnNext = document.getElementById('btnWizardNext');
    const btnSubmit = document.getElementById('btnWizardSubmit');
    const wizardForm = document.getElementById('networkDiagnosisForm');

    if (!wizardForm) return;

    // 2. Wizard Stepper UI Update
    function updateWizardUI() {
        stepItems.forEach((item, idx) => {
            const stepNum = idx + 1;
            item.classList.remove('active', 'completed');
            if (stepNum === currentStep) {
                item.classList.add('active');
            } else if (stepNum < currentStep) {
                item.classList.add('completed');
            }
        });

        stepPanels.forEach(panel => {
            const pStep = parseInt(panel.getAttribute('data-step'), 10);
            if (pStep === currentStep) {
                panel.classList.remove('d-none');
            } else {
                panel.classList.add('d-none');
            }
        });

        if (currentStep === 1) {
            btnPrev.classList.add('d-none');
        } else {
            btnPrev.classList.remove('d-none');
        }

        if (currentStep === totalSteps) {
            btnNext.classList.add('d-none');
            btnSubmit.classList.remove('d-none');
        } else {
            btnNext.classList.remove('d-none');
            btnSubmit.classList.add('d-none');
        }

        if (currentStep === 3) {
            evaluateAdaptiveQuestions();
        }

        window.scrollTo({ top: wizardForm.offsetTop - 70, behavior: 'smooth' });
    }

    // Next Button Click
    btnNext?.addEventListener('click', () => {
        if (validateCurrentStep()) {
            if (currentStep < totalSteps) {
                currentStep++;
                updateWizardUI();
            }
        }
    });

    // Prev Button Click
    btnPrev?.addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            updateWizardUI();
        }
    });

    function validateCurrentStep() {
        if (currentStep === 1) {
            const selectedConn = document.querySelector('input[name="connection_type"]:checked');
            if (!selectedConn) {
                alert('Silakan pilih salah satu jenis koneksi sebelum melanjutkan.');
                return false;
            }
        }
        return true;
    }

    // 3. Step 3: Evaluate Adaptive Questions Dynamically
    function evaluateAdaptiveQuestions() {
        const connType = document.querySelector('input[name="connection_type"]:checked')?.value || 'unknown';
        const checkedSymptoms = Array.from(document.querySelectorAll('input[name="symptoms[]"]:checked')).map(cb => parseInt(cb.value, 10));

        // Question: LAN LED
        const qLan = document.getElementById('adaptiveQuestionLan');
        if (qLan) {
            qLan.style.display = (connType === 'lan') ? 'block' : 'none';
        }

        // Question: WiFi Signal / SSID
        const qWifi = document.getElementById('adaptiveQuestionWifi');
        if (qWifi) {
            qWifi.style.display = (connType === 'wifi') ? 'block' : 'none';
        }

        // Question: Ping 8.8.8.8 vs Domain
        const qDns = document.getElementById('adaptiveQuestionDns');
        if (qDns) {
            qDns.style.display = 'block';
        }
    }

    // 4. Step 2: Symptom Search & Category Filter Pills
    const symptomSearch = document.getElementById('symptomSearch');
    const filterButtons = document.querySelectorAll('.symptom-filter-btn');
    let currentCategoryFilter = 'all';

    function filterSymptoms() {
        const query = (symptomSearch?.value || '').toLowerCase().trim();
        const items = document.querySelectorAll('.symptom-item');

        items.forEach(item => {
            const itemCat = item.getAttribute('data-category') || '';
            const text = item.textContent.toLowerCase();

            const matchesCat = (currentCategoryFilter === 'all' || itemCat === currentCategoryFilter);
            const matchesQuery = (query === '' || text.includes(query));

            if (matchesCat && matchesQuery) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    symptomSearch?.addEventListener('input', filterSymptoms);

    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            filterButtons.forEach(b => {
                b.classList.remove('btn-outline-primary', 'active');
                b.classList.add('btn-outline-secondary');
            });
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-outline-primary', 'active');

            currentCategoryFilter = btn.getAttribute('data-filter') || 'all';
            filterSymptoms();
        });
    });

    // 5. Step 4: Live Keyword Detection Preview (Local Deterministic)
    const customTextarea = document.getElementById('customIssueText');
    const detectedBox = document.getElementById('detectedKeywordsBox');
    const detectedList = document.getElementById('detectedKeywordsList');

    const knownKeywords = [
        'kabel', 'putus', 'rj45', 'lan', 'led', 'switch', 'mac address', 'duplex',
        'vlan', 'dhcp', '169.254', 'apipa', 'konflik ip', 'ip conflict', 'gateway',
        'subnet', 'port', 'firewall', 'timeout', 'dns', 'domain', 'nxdomain',
        'ssl', 'wifi', 'ssid', 'sinyal', 'lambat', 'lemot', 'ping', 'latency',
        'packet loss', 'rto', 'arp', 'spoofing'
    ];

    customTextarea?.addEventListener('input', () => {
        const text = customTextarea.value.toLowerCase();
        if (!text.trim()) {
            if (detectedBox) detectedBox.classList.add('d-none');
            return;
        }

        const found = [];
        knownKeywords.forEach(kw => {
            if (text.includes(kw)) {
                found.push(kw);
            }
        });

        if (found.length > 0) {
            detectedBox?.classList.remove('d-none');
            if (detectedList) {
                detectedList.innerHTML = found.map(k => `<span class="badge bg-primary px-2 py-1">${k}</span>`).join(' ');
            }
        } else {
            detectedBox?.classList.add('d-none');
        }
    });

});
