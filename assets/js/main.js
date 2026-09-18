/**
 * NetworkTrouble - Global Client-side JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Bootstrap Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // 2. Command Toolkit - One-click Copy to Clipboard
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const textToCopy = this.getAttribute('data-copy');
            if (!textToCopy) return;

            navigator.clipboard.writeText(textToCopy).then(() => {
                const originalHtml = this.innerHTML;
                this.innerHTML = '<i class="bi bi-check2 text-success"></i> Copied!';
                this.classList.add('text-success');
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                    this.classList.remove('text-success');
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        });
    });

    // 3. Admin Sidebar Mobile Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const adminSidebar = document.querySelector('.admin-sidebar');
    if (sidebarToggle && adminSidebar) {
        sidebarToggle.addEventListener('click', (e) => {
            e.preventDefault();
            adminSidebar.classList.toggle('show');
        });
    }

    // 4. Selectable Card selection helper
    document.querySelectorAll('.selectable-card').forEach(card => {
        const input = card.querySelector('input[type="radio"], input[type="checkbox"]');
        if (!input) return;

        // Sync visual class on initial load
        if (input.checked) {
            card.classList.add('selected');
        }

        // Listener directly on input change to ensure accurate state
        input.addEventListener('change', () => {
            if (input.type === 'radio') {
                const groupName = input.name;
                document.querySelectorAll(`input[name="${groupName}"]`).forEach(radio => {
                    radio.closest('.selectable-card')?.classList.toggle('selected', radio.checked);
                });
            } else if (input.type === 'checkbox') {
                card.classList.toggle('selected', input.checked);
            }
        });

        // Click on the card container
        card.addEventListener('click', (e) => {
            // If click was directly on the input or its label, the browser will natively toggle the input
            if (e.target === input || e.target.closest('label')?.htmlFor === input.id) {
                return;
            }

            if (input.type === 'radio') {
                input.checked = true;
            } else if (input.type === 'checkbox') {
                input.checked = !input.checked;
            }
            
            // Dispatch change event to trigger listeners and visual sync
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
});
