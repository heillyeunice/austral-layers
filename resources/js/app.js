import './bootstrap';

const staffThemePreferenceKey = 'austral-staff-theme';
const staffThemeToggle = document.querySelector('[data-staff-theme-toggle]');

if (document.body.classList.contains('staff-backend')) {
    let savedTheme = 'dark';

    try {
        savedTheme = localStorage.getItem(staffThemePreferenceKey) === 'light' ? 'light' : 'dark';
    } catch (error) {
        console.error('Unable to read the saved staff theme preference.', error);
    }

    const syncStaffThemeControl = () => {
        if (!(staffThemeToggle instanceof HTMLButtonElement)) return;

        const isLight = document.body.dataset.theme === 'light';
        const actionLabel = `Switch to ${isLight ? 'dark' : 'light'} mode`;
        staffThemeToggle.setAttribute('aria-label', actionLabel);
        staffThemeToggle.setAttribute('title', actionLabel);
        staffThemeToggle.setAttribute('aria-pressed', String(isLight));
    };

    document.body.dataset.theme = savedTheme;
    syncStaffThemeControl();

    if (staffThemeToggle instanceof HTMLButtonElement) {
        staffThemeToggle.addEventListener('click', () => {
            const theme = document.body.dataset.theme === 'light' ? 'dark' : 'light';
            document.body.dataset.theme = theme;
            syncStaffThemeControl();

            try {
                localStorage.setItem(staffThemePreferenceKey, theme);
            } catch (error) {
                console.error('Unable to save the staff theme preference.', error);
            }
        });
    }
}

const modal = document.querySelector('#quote-modal');
const openButtons = document.querySelectorAll('[data-open-quote]');
const closeButtons = document.querySelectorAll('[data-close-quote]');

function openQuoteModal() {
    modal?.classList.add('is-open');
    modal?.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    modal?.querySelector('input')?.focus();
}

function closeQuoteModal() {
    modal?.classList.remove('is-open');
    modal?.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
}

openButtons.forEach((button) => button.addEventListener('click', openQuoteModal));
closeButtons.forEach((button) => button.addEventListener('click', closeQuoteModal));
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeQuoteModal();
});

if (document.querySelector('.form-errors')) openQuoteModal();

const toast = document.querySelector('.success-toast');
if (toast) window.setTimeout(() => toast.classList.add('is-visible'), 100);

const inspirationInput = document.querySelector('#quote-inspiration');
const inspirationStatus = document.querySelector('#inspiration-status');
inspirationInput?.addEventListener('change', () => {
    if (inspirationInput.files?.length && inspirationStatus) {
        inspirationStatus.textContent = Array.from(inspirationInput.files)
            .map((file) => file.name)
            .join(', ');
    } else if (inspirationStatus) {
        inspirationStatus.textContent = 'No files selected.';
    }
});

document.querySelectorAll('[data-idempotent-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        const submitButton = form.querySelector('[data-idempotent-submit]');
        if (submitButton instanceof HTMLButtonElement) {
            submitButton.disabled = true;
            submitButton.textContent = form.dataset.submittingLabel ?? 'Submitting...';
        }
    });
});

document.querySelectorAll('[data-file-input]').forEach((input) => {
    if (!(input instanceof HTMLInputElement)) return;

    const status = input.closest('label')?.querySelector('[data-file-input-status]');
    if (!(status instanceof HTMLElement)) return;

    const emptyMessage = status.textContent?.trim() || 'No files selected.';
    input.addEventListener('change', () => {
        const files = Array.from(input.files ?? []).map((file) => file.name);
        const countLabel = files.length === 1 ? '1 file' : `${files.length} files`;
        status.textContent = files.length ? `${countLabel} selected: ${files.join(', ')}` : emptyMessage;
    });
});

document.querySelectorAll('[data-confirm-archive]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirmArchive ?? 'Move this record to the archive?')) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('[data-confirm-action]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirmAction ?? 'Continue with this action?')) {
            event.preventDefault();
        }
    });
});

const printCalculator = document.querySelector('[data-print-calculator]');
if (printCalculator instanceof HTMLElement) {
    const form = printCalculator.querySelector('#print-calculator-form');
    const brandSelect = form?.querySelector('[data-printer-brand]');
    const modelSelect = form?.querySelector('[data-printer-model]');
    const presetIdInput = form?.querySelector('[data-printer-preset-id]');
    const powerInput = form?.querySelector('[data-printer-power]');
    const priceInput = form?.querySelector('[data-printer-price]');
    const lifespanInput = form?.querySelector('[data-printer-lifespan]');
    const finalPrice = printCalculator.querySelector('[data-print-final-price]');
    const itemName = form?.querySelector('[name="item_name"]');
    const customerName = form?.querySelector('[name="customer_name"]');
    const filamentType = form?.querySelector('[name="filament_type"]');
    const weightInput = form?.querySelector('[name="weight_grams"]');
    const breakdown = printCalculator.querySelector('[data-print-breakdown]');
    const internalResult = printCalculator.querySelector('.business-print-result');
    const clientPreview = printCalculator.querySelector('[data-print-client-preview]');
    const clientLayout = printCalculator.querySelector('.business-print-layout');
    const viewInputs = printCalculator.querySelectorAll('[data-print-view]');

    if (
        form instanceof HTMLFormElement
        && brandSelect instanceof HTMLSelectElement
        && modelSelect instanceof HTMLSelectElement
        && presetIdInput instanceof HTMLInputElement
        && powerInput instanceof HTMLInputElement
        && priceInput instanceof HTMLInputElement
        && lifespanInput instanceof HTMLInputElement
        && finalPrice instanceof HTMLElement
    ) {
        const field = (name) => form.elements.namedItem(name);
        const value = (name) => {
            const input = field(name);
            return input instanceof HTMLInputElement || input instanceof HTMLSelectElement
                ? Number(input.value) || 0
                : 0;
        };
        const currency = new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
        const quantity = new Intl.NumberFormat('en-PH', { maximumFractionDigits: 2 });
        const setClientText = (selector, text) => {
            const element = printCalculator.querySelector(selector);
            if (element) element.textContent = text;
        };

        const updateEstimate = () => {
            const printHours = value('print_hours') + value('print_minutes') / 60;
            const laborHours = value('labor_hours') + value('labor_minutes') / 60;
            const material = value('weight_grams') / 1000 * value('filament_cost_per_kg');
            const electricity = value('printer_power_watts') / 1000 * printHours * value('electricity_rate');
            const printerWear = value('printer_lifespan_hours') > 0
                ? value('printer_price') / value('printer_lifespan_hours') * printHours
                : 0;
            const labor = value('labor_rate') * laborHours;
            const packaging = value('packaging_fee');
            const subtotal = material + electricity + printerWear + labor + packaging;
            const failure = subtotal * Number(printCalculator.dataset.failureRate) / 100;
            const profit = (subtotal + failure) * Number(printCalculator.dataset.markup) / 100;
            const total = subtotal + failure + profit;
            const half = Math.round(total / 2 * 100) / 100;
            const costs = {
                material_cost: material,
                electricity_cost: electricity,
                printer_wear_cost: printerWear,
                labor_cost: labor,
                packaging_fee: packaging,
                subtotal,
                failure_cost: failure,
                profit_amount: profit,
            };

            finalPrice.textContent = currency.format(total);
            const itemPreview = printCalculator.querySelector('[data-print-item-name]');
            if (itemPreview) {
                itemPreview.textContent = itemName instanceof HTMLInputElement && itemName.value.trim()
                    ? itemName.value.trim()
                    : 'Enter an item name to preview the quotation.';
            }
            printCalculator.querySelectorAll('[data-print-cost]').forEach((entry) => {
                const key = entry.getAttribute('data-print-cost');
                if (key && key in costs) entry.textContent = currency.format(costs[key]);
            });

            setClientText('[data-print-client-customer]', customerName instanceof HTMLInputElement && customerName.value.trim()
                ? customerName.value.trim()
                : 'Valued customer');
            setClientText('[data-print-client-item]', itemName instanceof HTMLInputElement && itemName.value.trim()
                ? itemName.value.trim()
                : 'Your 3D printed item');
            setClientText('[data-print-client-filament]', filamentType instanceof HTMLSelectElement ? filamentType.value : '—');
            setClientText('[data-print-client-weight]', `${quantity.format(value('weight_grams'))} g`);
            setClientText('[data-print-client-duration]', `${value('print_hours')} h ${value('print_minutes')} min`);
            setClientText('[data-print-client-total]', currency.format(total));
            setClientText('[data-print-client-half]', currency.format(half));
            setClientText('[data-print-client-balance]', `${currency.format(half)} + shipping fee`);
            setClientText('[data-print-client-footer-total]', currency.format(total));

            const mode = Array.from(viewInputs).find((input) => input instanceof HTMLInputElement && input.checked)?.value;
            printCalculator.dataset.viewMode = mode === 'client' ? 'client' : 'internal';
            if (breakdown instanceof HTMLElement) breakdown.hidden = mode === 'client';
            if (internalResult instanceof HTMLElement) internalResult.hidden = mode === 'client';
            if (clientPreview instanceof HTMLElement) clientPreview.hidden = mode !== 'client';
            if (clientLayout instanceof HTMLElement) clientLayout.dataset.viewMode = mode === 'client' ? 'client' : 'internal';
        };

        const updatePrinterModels = (preserveSelection) => {
            const selectedBrand = brandSelect.value;
            Array.from(modelSelect.options).forEach((option) => {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                option.hidden = option.dataset.brand !== selectedBrand;
                option.disabled = option.hidden;
            });
            modelSelect.disabled = selectedBrand === '';

            const selectedOption = modelSelect.selectedOptions[0];
            if (!preserveSelection || (selectedOption?.value && selectedOption.hidden)) {
                modelSelect.value = '';
                presetIdInput.value = '';
            }
        };

        const applyPrinterPreset = () => {
            const option = modelSelect.selectedOptions[0];
            if (!option?.value || option.hidden) {
                presetIdInput.value = '';
                return;
            }

            presetIdInput.value = option.dataset.presetId ?? '';
            powerInput.value = option.dataset.power ?? '';
            priceInput.value = option.dataset.price ?? '';
            lifespanInput.value = option.dataset.lifespan ?? '';
            updateEstimate();
        };

        updatePrinterModels(true);
        if (modelSelect.value) applyPrinterPreset();
        brandSelect.addEventListener('change', () => {
            updatePrinterModels(false);
            updateEstimate();
        });
        modelSelect.addEventListener('change', applyPrinterPreset);
        form.addEventListener('input', updateEstimate);
        form.addEventListener('change', updateEstimate);
        viewInputs.forEach((input) => input.addEventListener('change', updateEstimate));
        updateEstimate();
    }
}

document.querySelectorAll('[data-document-workspace]').forEach((workspace) => {
    const typeSwitch = workspace.querySelector('[data-document-type-switch]');
    const panels = workspace.querySelectorAll('[data-document-panel]');
    if (!(typeSwitch instanceof HTMLElement)) return;

    const syncDocumentPanel = () => {
        const selectedType = typeSwitch.querySelector('[data-document-choice]:checked')?.value ?? 'invoice';
        panels.forEach((panel) => {
            panel.hidden = panel.getAttribute('data-document-panel') !== selectedType;
        });
    };

    typeSwitch.addEventListener('change', syncDocumentPanel);
    syncDocumentPanel();
});

const profilePhotoInput = document.querySelector('#profile-photo-input');
if (profilePhotoInput instanceof HTMLInputElement) {
    const preview = document.querySelector('[data-profile-photo-preview]');
    const initial = document.querySelector('[data-profile-photo-initial]');
    const fileName = document.querySelector('#profile-photo-file-name');
    const originalSource = preview?.getAttribute('src') ?? '';
    let previewUrl = null;

    profilePhotoInput.addEventListener('change', () => {
        const file = profilePhotoInput.files?.[0];

        if (!file) {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = null;
            if (preview instanceof HTMLImageElement) {
                if (originalSource) preview.src = originalSource;
                else preview.removeAttribute('src');
                preview.hidden = !originalSource;
            }
            if (initial instanceof HTMLElement) initial.hidden = Boolean(originalSource);
            if (fileName) fileName.textContent = 'No new photo selected.';

            return;
        }

        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        if (preview instanceof HTMLImageElement) {
            preview.src = previewUrl;
            preview.hidden = false;
        }
        if (initial instanceof HTMLElement) initial.hidden = true;
        if (fileName) fileName.textContent = `${file.name} selected. Choose Update photo to save it.`;
    });
}

const secondMainColorToggle = document.querySelector('#use-second-main-color');
const secondMainColorInput = document.querySelector('#brand-secondary-color');
if (secondMainColorToggle instanceof HTMLInputElement && secondMainColorInput instanceof HTMLInputElement) {
    const syncSecondMainColor = () => {
        secondMainColorInput.disabled = !secondMainColorToggle.checked;
        secondMainColorInput.required = secondMainColorToggle.checked;
    };

    syncSecondMainColor();
    secondMainColorToggle.addEventListener('change', syncSecondMainColor);
}

document.querySelectorAll('[data-portfolio-gallery]').forEach((gallery) => {
    const mainImage = gallery.querySelector('[data-gallery-main]');
    const caption = gallery.querySelector('[data-gallery-caption]');
    const thumbnails = gallery.querySelectorAll('[data-gallery-thumb]');

    thumbnails.forEach((thumbnail) => {
        thumbnail.addEventListener('click', () => {
            const source = thumbnail.getAttribute('data-gallery-src');
            const alt = thumbnail.getAttribute('data-gallery-alt');
            const description = thumbnail.getAttribute('data-gallery-caption');

            if (!mainImage || !caption || !source || !alt || !description) return;

            mainImage.src = source;
            mainImage.alt = alt;
            caption.textContent = description;
            thumbnails.forEach((item) => item.setAttribute('aria-pressed', item === thumbnail ? 'true' : 'false'));
        });
    });
});

const orderTracker = document.querySelector('[data-order-tracker]')?.closest('.business-tracker');
if (orderTracker instanceof HTMLElement) {
    let draggedCard = null;
    const statusMessage = orderTracker.querySelector('[data-tracker-status]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const orderCards = orderTracker.querySelector('.business-tracker-orders');

    const updateStageCounts = () => {
        const counts = new Map();
        orderCards?.querySelectorAll('[data-order-card]').forEach((card) => {
            const status = card.dataset.status;
            if (status) counts.set(status, (counts.get(status) ?? 0) + 1);
        });

        orderTracker.querySelectorAll('[data-status-target]').forEach((stage) => {
            const status = stage.dataset.statusTarget;
            const count = status ? counts.get(status) ?? 0 : 0;
            const countElement = status ? stage.querySelector(`[data-stage-count="${status}"]`) : null;
            if (countElement) countElement.textContent = String(count);
            if (status) {
                const label = stage.querySelector('span')?.textContent?.trim() ?? 'stage';
                stage.setAttribute('aria-label', `Drop an order here to move it to ${label}; ${count} orders`);
            }
        });
    };

    const updateCardProgress = (card, status) => {
        card.dataset.status = status;
        const select = card.querySelector('[data-order-status-select]');
        if (select instanceof HTMLSelectElement) select.value = status;

        const steps = Array.from(card.querySelectorAll('[data-progress-step]'));
        const currentStep = steps.findIndex((step) => step.getAttribute('data-progress-step') === status);
        const statusStep = steps.find((step) => step.getAttribute('data-progress-step') === status);
        const statusIndex = Number(statusStep?.dataset.stageIndex ?? -1);
        const furthestStatusIndex = Math.max(Number(card.dataset.furthestStatusIndex ?? -1), statusIndex);
        card.dataset.furthestStatusIndex = String(furthestStatusIndex);
        steps.forEach((step, index) => {
            step.classList.toggle('is-complete', currentStep >= 0 && index < currentStep);
            step.classList.toggle('is-current', index === currentStep);
            if (index === currentStep) step.setAttribute('aria-current', 'step');
            else step.removeAttribute('aria-current');

            const assignee = step.querySelector('[data-stage-assignee-select]');
            if (assignee instanceof HTMLSelectElement) {
                const stageIndex = Number(step.dataset.stageIndex ?? -1);
                const locked = stageIndex < furthestStatusIndex;
                assignee.disabled = locked;
                const label = assignee.closest('label')?.querySelector('span');
                if (label) label.textContent = locked ? 'Fixed' : 'Staff';
            }
        });
    };

    const changeOrderStatus = async (card, nextStatus) => {
        const previousStatus = card.dataset.status;
        const statusUrl = card.dataset.statusUrl;
        const select = card.querySelector('[data-order-status-select]');
        if (!nextStatus || nextStatus === previousStatus || !statusUrl) return;
        if (!csrfToken) {
            if (statusMessage) statusMessage.textContent = 'The order status could not be updated. Refresh and try again.';
            if (select instanceof HTMLSelectElement) select.value = previousStatus ?? '';
            return;
        }

        card.classList.add('is-saving');
        if (select instanceof HTMLSelectElement) select.disabled = true;

        try {
            const response = await fetch(statusUrl, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ status: nextStatus }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message ?? 'The order status could not be updated.');

            if (result.status === 'completed' || result.status === 'cancelled') {
                window.location.reload();
                return;
            }

            updateCardProgress(card, result.status);
            updateStageCounts();
            if (statusMessage) {
                const orderName = card.querySelector('.business-tracker-order-link strong')?.textContent ?? 'Order';
                statusMessage.textContent = `${orderName} moved to ${result.label}.`;
            }
        } catch (error) {
            if (select instanceof HTMLSelectElement) select.value = previousStatus ?? '';
            if (statusMessage) statusMessage.textContent = error instanceof Error ? error.message : 'The order status could not be updated. Refresh and try again.';
        } finally {
            card.classList.remove('is-saving');
            if (select instanceof HTMLSelectElement) select.disabled = false;
        }
    };

    const changeStageAssignee = async (select) => {
        const previousAssignee = select.dataset.savedAssignee ?? '';
        const nextAssignee = select.value;
        const stage = select.dataset.stage;
        const assignmentUrl = select.dataset.assignmentUrl;
        const card = select.closest('[data-order-card]');
        if (!stage || !assignmentUrl || nextAssignee === previousAssignee) return;
        if (!csrfToken) {
            if (statusMessage) statusMessage.textContent = 'The staff assignment could not be updated. Refresh and try again.';
            select.value = previousAssignee;
            return;
        }

        if (card) card.classList.add('is-saving');
        select.disabled = true;

        try {
            const response = await fetch(assignmentUrl, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    stage,
                    assigned_staff_id: nextAssignee || null,
                }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message ?? 'The staff assignment could not be updated.');

            select.dataset.savedAssignee = result.assigned_staff_id?.toString() ?? '';
            if (statusMessage) {
                const orderName = card?.querySelector('.business-tracker-order-link strong')?.textContent ?? 'Order';
                statusMessage.textContent = `${orderName}: ${result.stage_label} assigned to ${result.staff_name ?? 'no one'}.`;
            }
        } catch (error) {
            select.value = previousAssignee;
            if (statusMessage) statusMessage.textContent = error instanceof Error ? error.message : 'The staff assignment could not be updated. Refresh and try again.';
        } finally {
            if (card) card.classList.remove('is-saving');
            select.disabled = false;
        }
    };

    orderTracker.addEventListener('change', (event) => {
        if (!(event.target instanceof HTMLSelectElement)) return;
        if (event.target.matches('[data-stage-assignee-select]')) {
            changeStageAssignee(event.target);
            return;
        }
        if (!event.target.matches('[data-order-status-select]')) return;

        const card = event.target.closest('[data-order-card]');
        if (card instanceof HTMLElement) changeOrderStatus(card, event.target.value);
    });

    orderTracker.addEventListener('dblclick', (event) => {
        if (!(event.target instanceof Element)) return;
        const card = event.target.closest('[data-order-card]');
        if (!(card instanceof HTMLElement) || event.target.closest('a, button, input, select, textarea')) return;

        const orderUrl = card.dataset.orderUrl;
        if (orderUrl) window.location.assign(orderUrl);
    });

    orderTracker.addEventListener('dragstart', (event) => {
        if (!(event.target instanceof Element)) return;
        const card = event.target.closest('[data-order-card]');
        if (!(card instanceof HTMLElement) || event.target.closest('a, button, input, select, textarea')) return;

        draggedCard = card;
        card.classList.add('is-dragging');
        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.dataset.statusUrl ?? '');
        }
    });

    orderTracker.addEventListener('dragend', () => {
        draggedCard?.classList.remove('is-dragging');
        orderTracker.querySelectorAll('.is-drop-target').forEach((stage) => stage.classList.remove('is-drop-target'));
        draggedCard = null;
    });

    orderTracker.addEventListener('dragover', (event) => {
        if (!(event.target instanceof Element) || !event.target.closest('[data-status-target]')) return;
        event.preventDefault();
        if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
    });

    orderTracker.addEventListener('dragenter', (event) => {
        if (!(event.target instanceof Element)) return;
        event.target.closest('[data-status-target]')?.classList.add('is-drop-target');
    });

    orderTracker.addEventListener('dragleave', (event) => {
        if (!(event.target instanceof Element)) return;
        const stage = event.target.closest('[data-status-target]');
        if (stage && !stage.contains(event.relatedTarget)) stage.classList.remove('is-drop-target');
    });

    orderTracker.addEventListener('drop', (event) => {
        if (!(event.target instanceof Element) || !draggedCard) return;
        const targetStage = event.target.closest('[data-status-target]');
        if (!targetStage) return;
        event.preventDefault();
        targetStage.classList.remove('is-drop-target');
        changeOrderStatus(draggedCard, targetStage.getAttribute('data-status-target'));
    });
}
