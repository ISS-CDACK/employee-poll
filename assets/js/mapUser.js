let choicesInstance = null; // Declare a variable to hold the Choices instance for group head
let multiChoicesInstance = null; // Declare a variable to hold the Choices instance for group members

function openGroupModal(groupName, GroupID) {
    document.getElementById('groupId').value = GroupID;
    document.getElementById('modal-title').innerText = 'Assign Group Members for ' + groupName;

    showSpinner(); // Show spinner immediately
    fetchGroupHeadNames(GroupID); // Fetch group head names, and the modal will be shown after data is loaded
}

function groupCancel() {
    // Clear the form fields if necessary
    document.getElementById('addNewGroup').reset();

    if (choicesInstance) {
        choicesInstance.destroy(); // Destroy the Choices instance for group head
    }

    if (multiChoicesInstance) {
        multiChoicesInstance.destroy(); // Destroy the Choices instance for group members
    }
}

function fetchGroupHeadNames(GroupID) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "/api/fetchAllUserList", true);
    xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8"); // Set the content type to JSON

    xhr.onload = function () {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            const selectElement = document.getElementById("groupHeadName");
            const multiSelectElement = document.getElementById("groupMembersName");

            // Clear any existing options
            selectElement.innerHTML = '';
            multiSelectElement.innerHTML = '';

            selectElement.add(new Option("Please Select Group Head", "", true, true));

            // Populate the select elements with options from the AJAX response
            response.forEach(function (item) {
                const option = document.createElement("option");
                option.value = item.value;
                option.text = item.label;
                if (item.isGroupHead) { // Check if the item should be selected
                    option.selected = true;
                }
                selectElement.add(option);

                // Add the option to the group members' multi-select as well
                const multiOption = document.createElement("option");
                multiOption.value = item.value;
                multiOption.text = item.label;
                if (item.isGroupMember) { // Check if the item should be selected
                    multiOption.selected = true;
                }
                multiSelectElement.add(multiOption);
            });

            // Destroy existing Choices instance if it exists
            if (choicesInstance) {
                choicesInstance.destroy();
            }

            if (multiChoicesInstance) {
                multiChoicesInstance.destroy();
            }

            // Initialize new Choices instances
            choicesInstance = new Choices(selectElement, {
                allowHTML: true,
                delimiter: ",",
                editItems: true,
                maxItemCount: 1,
                removeItemButton: true,
                searchEnabled: true
            });

            multiChoicesInstance = new Choices(multiSelectElement, {
                allowHTML: true,
                delimiter: ",",
                editItems: true,
                maxItemCount: -1, // No limit on number of selected items
                removeItemButton: true,
                searchEnabled: true,
            });

            hideSpinner(); // Hide spinner after data is loaded
            var modal = new bootstrap.Modal(document.getElementById('modal-add-group'));
            modal.show(); // Show modal after spinner is hidden
        } else {
            hideSpinner(); // Hide spinner even if the request fails
            alert("Failed to load data. Please try again."); // Handle error as needed
        }
    };

    xhr.onerror = function() {
        hideSpinner(); // Hide spinner if the request fails
        alert("An error occurred during the request. Please try again."); // Handle error as needed
    };

    xhr.send(JSON.stringify({
        groupId: GroupID
    }));
}


const form = document.getElementById('addNewGroup');
form.addEventListener('submit', function (event) {
    const groupHeadName = document.getElementById('groupHeadName').value;
    const groupMembersName = document.getElementById('groupMembersName').selectedOptions.length;

    if (!groupHeadName || groupMembersName === 0) {
        // alert('Please select a Group Head and at least one Group Member.');
        showToast(5000, 'warning', 'Please select a Group Head and at least one Group Member.');
        event.preventDefault(); // Prevent form submission
    }
    showSpinner();
});