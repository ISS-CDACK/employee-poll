"use strict";
const addGroupModal = document.getElementById('modal-add-group');
const groupTitle = document.getElementById('groupName');
const modalButton = document.getElementById('addGroup');
const modalTitle = document.getElementById('modal-title');
const customAttribute = document.getElementById('setName');

// document.addEventListener("DOMContentLoaded", function (e) {
    // hideSpinner();
// });

const groupModal = new bootstrap.Modal(addGroupModal, {
    keyboard: false
});

function showAddGroupModal() {
    modalButton.innerHTML = "Add New Group";
    modalTitle.innerHTML = "Add New Group";
    customAttribute.value = "";
    groupModal.show();
}

function groupCancel() {
    groupTitle.value = "";
    groupModal.hide();
}

document.getElementById("addNewGroup").addEventListener("submit", function (event) {
    event.preventDefault();

    const form = event.target;
    const groupInput = document.getElementById('groupName');
    const groupValue = groupInput.value.trim();

    let msg = '';

    if (groupValue === '') {
        msg = 'Please enter a group name,';
    }

    if (msg != '') {
        showToast(5000, 'warning',  msg + ' and try again');
        return;
    }

    if (modalButton.innerHTML === "Add New Group") {
        let extraParam = document.createElement("input");
        extraParam.type = "hidden";
        extraParam.name = "addNewGroup";
        extraParam.value = "addNewGroup";
        form.appendChild(extraParam);
        showSpinner();
        form.submit();
    } else if (modalButton.innerHTML === "Save Group Name") {
        let extraParam = document.createElement("input");
        let extraParam2 = document.createElement("input");
        extraParam.type = "hidden";
        extraParam.name = "editGroup";
        extraParam.value = "editGroup";
        extraParam2.type = "hidden";
        extraParam2.name = "setName";
        extraParam2.value = customAttribute.value;
        form.appendChild(extraParam);
        form.appendChild(extraParam2);
        showSpinner();
        form.submit();
    }
});

function editGroup(gid, value) {
    modalButton.innerHTML = "Save Group Name";
    modalTitle.innerHTML = "Update Group Name";
    customAttribute.value = gid;
    groupTitle.value = value;
    groupModal.show();
}

function deleteGroup(gid) {
    Swal.fire({
        title: 'Are you sure?',
        text: "Do you really want to delete this group?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            let form = document.createElement("form");
            form.method = "post";
            form.action = window.location.href;

            let groupValue = document.createElement("input");
            groupValue.type = "hidden";
            groupValue.name = "groupID";
            groupValue.value = gid;
            form.appendChild(groupValue);

            document.body.appendChild(form);
            showSpinner(); // Show the spinner or any loading animation
            form.submit();
        }
    });
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
    excelModal.hide();
}

excelFileInput.addEventListener('change', function () {
    const allowedExtensions = ['xls', 'xlsx', 'csv', 'tsv'];
    const fileName = excelFileInput.value.split('\\').pop();
    const fileExtension = fileName.split('.').pop().toLowerCase();

    if (!allowedExtensions.includes(fileExtension)) {
        showToast(5000, 'warning', 'Invalid File, Only Excel (.xls,.xlsx,.csv,.tsv) files are allowed');
        excelFileInput.value = "";
    }
});

document.getElementById("uploadExcelForm").addEventListener("submit", function (event) {
    // Prevent the default form submission
    if (excelFileInput.value == "") {
        showToast(5000, 'warning', 'Invalid File, Please select an Excel (.xls,.xlsx,.csv,.tsv) file');
        event.preventDefault();
        return;
    }
    showSpinner();
});
