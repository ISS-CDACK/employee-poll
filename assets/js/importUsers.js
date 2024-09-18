let table = $("#ldapDataTable").DataTable({
    responsive: true,
});

const setTableColor = () => {
    document.querySelectorAll('.dataTables_paginate .pagination').forEach(dt => {
        dt.classList.add('pagination-primary');
    });
};
setTableColor();

// Toggle all checkboxes across all pages
document.getElementById('selectAll').addEventListener('click', function() {
    const isChecked = this.checked;

    // Loop through each row in the table, and find the checkbox within each row
    table.rows().every(function() {
        const checkbox = this.node().querySelector('.user-checkbox');
        if (checkbox) {
            checkbox.checked = isChecked;
        }
    });
});



// Excel Modal for uploading excel file
const selectGroupModal = new bootstrap.Modal(document.getElementById('modal-select-group'), {
    keyboard: false
});


function selectGroupModalCancel() {
    choices.setChoiceByValue('false');
    selectGroupModal.hide();
}

// Log checked items
document.getElementById('logCheckedItems').addEventListener('click', function() {
    let checkedValues = [];

    // Loop through all rows to find checked checkboxes
    table.rows().every(function() {
        const checkbox = this.node().querySelector('.user-checkbox:checked');
        if (checkbox) {
            checkedValues.push(checkbox.value);
        }
    });

    if (checkedValues.length === 0) {
        showToast(5000, 'warning', 'Nothing is Selected.');
        return;
    }

    // Populate the hidden form input with the checked values
    document.getElementById('usersInput').value = checkedValues.join(',');

    // Show the modal for group selection
    selectGroupModal.show();
});

// Function to set the group name and submit the form
function setGroupName() {
    // Get the selected group name from the modal
    const selectedGroup = document.getElementById('group-select').value;
    document.getElementById('groupId').value = selectedGroup;
    showSpinner();
    document.getElementById('importUsersForm').submit();
}


// Excel Modal for uploading excel file
const excelFileInput = document.getElementById('excelFile');
const excelModal = new bootstrap.Modal(document.getElementById('modal-upload-excel'), {
    keyboard: false
});

function showExcelModal() {
    excelFileInput.value = "";
    excelModal.show();
}

function excelCancel() {
    excelFileInput.value = "";
    choices2.setChoiceByValue('false');
    excelModal.hide();
}

excelFileInput.addEventListener('change', function() {
    const allowedExtensions = ['xls', 'xlsx', 'csv', 'tsv'];
    const fileName = excelFileInput.value.split('\\').pop();
    const fileExtension = fileName.split('.').pop().toLowerCase();

    if (!allowedExtensions.includes(fileExtension)) {
        showToast(5000, 'warning', 'Invalid File, Only Excel (.xls,.xlsx,.csv,.tsv) files are allowed');
        excelFileInput.value = "";
    }
});

document.getElementById("uploadExcelForm").addEventListener("submit", function(event) {
    // Prevent the default form submission
    if (excelFileInput.value == "") {
        showToast(5000, 'warning', 'Invalid File, Please select an Excel (.xls,.xlsx,.csv,.tsv) file');
        event.preventDefault();
        return;
    }
    showSpinner();
});

const selectElement = document.getElementById('group-select');
const choices = new Choices(selectElement, {
    allowHTML: true,
    searchEnabled: true,
    position: 'single', // Ensure position is 'single' to show single select options
    placeholder: true, // Allows a placeholder if needed
});


const selectElement2 = document.getElementById('group-select-2');
const choices2 = new Choices(selectElement2, {
    allowHTML: true,
    searchEnabled: true,
    position: 'single', // Ensure position is 'single' to show single select options
    placeholder: true, // Allows a placeholder if needed
});
