let table = $("#table1").DataTable({
    responsive: true,
    columnDefs: [
        {
            targets: 7, // Index of the column you want to disable sorting for
            orderable: false // Disables sorting for this column
        }
    ]
});

const setTableColor = () => {
    document.querySelectorAll('.dataTables_paginate .pagination').forEach(dt => {
        dt.classList.add('pagination-primary')
    })
}
setTableColor();

// Function to get unique values from the specified column index in the table
const getUniqueValues = (index) => {
    return [...new Set([...document.querySelectorAll('#table1 tbody tr')].map(row => row.children[index].textContent.trim()))];
};

// Function to initialize Choices.js for a given select element
const initializeChoices = (selectElementId, columnIndex) => {
    const selectElement = document.getElementById(selectElementId);
    const choices = new Choices(selectElement, {
        allowHTML: true,
        searchEnabled: true,
        position: 'single',
        placeholder: true,
        shouldSort: false,
    });
    
    const uniqueValues = getUniqueValues(columnIndex);
    // const options = [{ value: '', label: 'Show All' }].concat(uniqueValues.map(value => ({ value, label: value })));
    const options = [{ value: '', label: 'Show All' }]
    .concat(
      uniqueValues
        .sort((a, b) => a.localeCompare(b)) // Sort alphabetically
        .map(value => ({ value, label: value })) // Map to { value, label } structure
    );
    choices.setChoices(options, 'value', 'label', true);

    // Set default selected option
    selectElement.value = '';
    choices.setChoiceByValue('');

    selectElement.addEventListener('change', function () {
        const selectedValue = this.value;
        if (selectElementId === 'filterStatus') {
            if (selectedValue === 'Active') {
                table.column(columnIndex).search('^Active$', true, false).draw();
            } else if (selectedValue === 'Inactive') {
                table.column(columnIndex).search('^Inactive$', true, false).draw();
            } else {
                table.column(columnIndex).search('').draw();
            }
        } else {
            const selectedValue = this.value;
            table.column(columnIndex).search(selectedValue).draw();
        }
    });
};

// Initialize Choices.js for each filter
initializeChoices('filterGroup', 3);
initializeChoices('filterStatus', 6);
initializeChoices('filterGroupHead', 5);
initializeChoices('filterRole', 4);