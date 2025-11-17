// --- GLOBAL MODAL & TOAST FUNCTIONS ---
let toastTimeout;

function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('hidden');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('hidden');
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    const toastIcon = document.getElementById('toast-icon');
    const toastContainer = toast?.parentElement;
    if (!toast || !toastMessage || !toastIcon) return;

    clearTimeout(toastTimeout);
    toastMessage.innerHTML = message;

    if (type === 'success') {
        toastIcon.className = 'inline-flex items-center justify-center w-8 h-8 text-emerald-600 bg-emerald-100 rounded-full';
        toastIcon.innerHTML = '<i class="fas fa-check text-sm"></i>';
        toast.parentElement.classList.add('border-emerald-200', 'bg-emerald-50');
    } else if (type === 'error') {
        toastIcon.className = 'inline-flex items-center justify-center w-8 h-8 text-red-600 bg-red-100 rounded-full';
        toastIcon.innerHTML = '<i class="fas fa-times text-sm"></i>';
        toast.parentElement.classList.add('border-red-200', 'bg-red-50');
    } else {
        toastIcon.className = 'inline-flex items-center justify-center w-8 h-8 text-blue-600 bg-blue-100 rounded-full';
        toastIcon.innerHTML = '<i class="fas fa-info-circle text-sm"></i>';
        toast.parentElement.classList.add('border-blue-200', 'bg-blue-50');
    }

    toast.classList.remove('translate-x-[120%]', 'opacity-0');
    toastTimeout = setTimeout(hideToast, 5000);
}

function hideToast() {
    const toast = document.getElementById('toast');
    const toastContainer = toast?.parentElement;
    if (toast) {
        toast.classList.add('translate-x-[120%]', 'opacity-0');
        setTimeout(() => {
            toastContainer.classList.remove('border-emerald-200', 'bg-emerald-50', 'border-red-200', 'bg-red-50', 'border-blue-200', 'bg-blue-50');
        }, 300);
    }
}

// --- MEMBER-SPECIFIC MODAL FUNCTIONS ---

function openAddMemberModal() {
    const form = document.getElementById('memberForm');
    form.reset();
    
    document.getElementById('modalTitle').textContent = 'Add New Member';
    document.getElementById('submitButton').textContent = 'Add Member';
    document.getElementById('form_action').value = 'add_member';
    document.getElementById('form_member_id').value = '';
    
    document.getElementById('form_join_date').value = new Date().toISOString().slice(0, 10);
    document.getElementById('status-field').classList.add('hidden');

    // Hide in-modal delete button in add mode
    const editDeleteButton = document.getElementById('editDeleteButton');
    if (editDeleteButton) {
        editDeleteButton.classList.add('hidden');
        editDeleteButton.onclick = null;
    }
    
    showModal('memberModal');
}

function openEditMemberModal(memberData) {
    const form = document.getElementById('memberForm');
    form.reset();

    document.getElementById('modalTitle').textContent = 'Edit Member';
    document.getElementById('submitButton').textContent = 'Save Changes';
    document.getElementById('form_action').value = 'edit_member';

    document.getElementById('form_member_id').value = memberData.id;
    document.getElementById('form_name').value = memberData.name;
    document.getElementById('form_phone').value = memberData.phone;
    document.getElementById('form_contribution_amount').value = memberData.contribution_amount;
    document.getElementById('form_group_id').value = memberData.group_id;
    document.getElementById('form_join_date').value = memberData.join_date;
    document.getElementById('form_status').value = memberData.status;
    
    document.getElementById('status-field').classList.remove('hidden');

    // Show and wire in-modal delete button for this member
    const editDeleteButton = document.getElementById('editDeleteButton');
    if (editDeleteButton) {
        editDeleteButton.classList.remove('hidden');
        editDeleteButton.onclick = function () {
            closeModal('memberModal');
            openDeleteMemberModal(memberData.id, memberData.name);
        };
    }
    showModal('memberModal');
}

function openDeleteMemberModal(memberId, memberName) {
    document.getElementById('delete_member_id').value = memberId;
    document.getElementById('deleteMessage').textContent = `Are you sure you want to delete "${memberName}"? All their payment records will also be deleted.`;
    showModal('deleteModal');
}

// --- EXPENSE-SPECIFIC MODAL FUNCTIONS ---

function openAddExpenseModal() {
    const form = document.getElementById('expenseForm');
    form.reset();
    
    document.getElementById('expenseModalTitle').textContent = 'Add New Expense';
    document.getElementById('expenseSubmitButton').textContent = 'Add Expense';
    document.getElementById('expense_form_action').value = 'add_expense';
    document.getElementById('expense_form_id').value = '';
    document.getElementById('expense_form_date').value = new Date().toISOString().slice(0, 10);
    
    showModal('expenseModal');
}

function openEditExpenseModal(expenseData) {
    document.getElementById('expenseModalTitle').textContent = 'Edit Expense';
    document.getElementById('expenseSubmitButton').textContent = 'Save Changes';
    document.getElementById('expense_form_action').value = 'edit_expense';

    document.getElementById('expense_form_id').value = expenseData.id;
    document.getElementById('expense_form_title').value = expenseData.title;
    document.getElementById('expense_form_amount').value = expenseData.amount;
    document.getElementById('expense_form_group_id').value = expenseData.group_id;
    document.getElementById('expense_form_date').value = expenseData.expense_date;
    document.getElementById('expense_form_description').value = expenseData.description;

    showModal('expenseModal');
}

function openDeleteExpenseModal(expenseId, expenseTitle) {
    document.getElementById('delete_expense_id').value = expenseId;
    document.getElementById('deleteExpenseMessage').textContent = `Are you sure you want to delete the expense "${expenseTitle}"?`;
    showModal('deleteExpenseModal');
}

// --- PAYMENT UPDATE LOGIC ---

function openUpdatePaymentModal(memberData) {
    const totalDue = memberData.total_due || memberData.contribution_amount;

    document.getElementById('update_member_id').value = memberData.member_id;
    document.getElementById('update_member_name').textContent = memberData.member_name;
    document.getElementById('update_total_amount_due').value = totalDue;
    document.getElementById('total_due_display').textContent = '₹' + parseFloat(totalDue).toLocaleString('en-IN');
    document.getElementById('update_notes').value = memberData.notes || '';

    const fullPaymentRadio = document.querySelector('input[name="payment_type"][value="full"]');
    const partialPaymentRadio = document.querySelector('input[name="payment_type"][value="partial"]');
    const amountPaidWrapper = document.getElementById('amount_paid_wrapper');
    const amountPaidInput = document.getElementById('update_amount_paid');

    const toggleAmountInput = () => {
        if (partialPaymentRadio.checked) {
            amountPaidWrapper.classList.remove('hidden');
            amountPaidInput.value = parseFloat(memberData.amount_paid || 0).toFixed(2);
            amountPaidInput.setAttribute('required', 'required');
        } else {
            amountPaidWrapper.classList.add('hidden');
            amountPaidInput.value = totalDue;
            amountPaidInput.removeAttribute('required');
        }
    };

    fullPaymentRadio.addEventListener('change', toggleAmountInput);
    partialPaymentRadio.addEventListener('change', toggleAmountInput);

    if (memberData.status === 'Partial') {
        partialPaymentRadio.checked = true;
    } else {
        fullPaymentRadio.checked = true;
    }
    toggleAmountInput();
    
    showModal('updatePaymentModal');
}

// Add an event listener to the whole document for payment update buttons
document.addEventListener('click', function(event) {
    // Allow clicks on the button OR any child inside it
    const button = event.target.closest('.js-update-payment-btn');
    if (button) {
        // Create an object from the button's data attributes
        const memberData = {
            member_id: button.dataset.memberId,
            member_name: button.dataset.memberName,
            contribution_amount: button.dataset.contributionAmount,
            payment_id: button.dataset.paymentId,
            total_due: button.dataset.totalDue,
            amount_paid: button.dataset.amountPaid,
            notes: button.dataset.notes,
            status: button.dataset.status
        };
        // Call our function with the collected data
        openUpdatePaymentModal(memberData);
    }
});

// --- MOBILE SIDEBAR TOGGLE + MEMBERS SEARCH FILTER ---
document.addEventListener('DOMContentLoaded', () => {
    // Mobile sidebar toggle
    const menuButton = document.getElementById('mobile-menu-button');
    const sidebar = document.getElementById('sidebar');

    if (menuButton && sidebar) {
        menuButton.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });
    }

    // Members page search filter
    const memberSearchInput = document.getElementById('memberSearchInput');
    if (memberSearchInput) {
        const memberMobileCards = document.querySelectorAll('.js-member-card');
        const memberDesktopRows = document.querySelectorAll('.js-member-row');

        const applyMemberFilter = () => {
            const q = memberSearchInput.value.trim().toLowerCase();

            if (!q) {
                memberMobileCards.forEach(card => card.classList.remove('hidden'));
                memberDesktopRows.forEach(row => row.classList.remove('hidden'));
                return;
            }

            const matches = (el) => {
                const name = (el.getAttribute('data-name') || '').toLowerCase();
                const uid = (el.getAttribute('data-uid') || '').toLowerCase();
                const phone = (el.getAttribute('data-phone') || '').toLowerCase();
                return name.includes(q) || uid.includes(q) || phone.includes(q);
            };

            memberMobileCards.forEach(card => {
                if (matches(card)) card.classList.remove('hidden');
                else card.classList.add('hidden');
            });

            memberDesktopRows.forEach(row => {
                if (matches(row)) row.classList.remove('hidden');
                else row.classList.add('hidden');
            });
        };

        memberSearchInput.addEventListener('input', applyMemberFilter);
    }

    // Payments page search filter
    const paymentSearchInput = document.getElementById('paymentSearchInput');
    if (paymentSearchInput) {
        const paymentMobileCards = document.querySelectorAll('.js-payment-card');
        const paymentDesktopRows = document.querySelectorAll('.js-payment-row');

        const applyPaymentFilter = () => {
            const q = paymentSearchInput.value.trim().toLowerCase();

            if (!q) {
                paymentMobileCards.forEach(card => card.classList.remove('hidden'));
                paymentDesktopRows.forEach(row => row.classList.remove('hidden'));
                return;
            }

            const matches = (el) => {
                const name = (el.getAttribute('data-name') || '').toLowerCase();
                const phone = (el.getAttribute('data-phone') || '').toLowerCase();
                return name.includes(q) || phone.includes(q);
            };

            paymentMobileCards.forEach(card => {
                if (matches(card)) card.classList.remove('hidden');
                else card.classList.add('hidden');
            });

            paymentDesktopRows.forEach(row => {
                if (matches(row)) row.classList.remove('hidden');
                else row.classList.add('hidden');
            });
        };

        paymentSearchInput.addEventListener('input', applyPaymentFilter);
    }
});