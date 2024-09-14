function toggleAccount(state) {
    // Get the UID parameter from the URL
    const urlParams = new URLSearchParams(window.location.search);
    const UID = urlParams.get('UID');

    // Prepare the data to send in the AJAX request
    const data = {
        state: state,
        UID: UID
    };

    // Perform the AJAX call
    $.ajax({
        url: '/api/updateAccount',
        method: 'POST',
        data: data,
        success: function(response) {
            // Update the UI based on the state
            if (state) {
                document.getElementById('state').innerHTML = 'Active';
            } else {
                document.getElementById('state').innerHTML = 'Inactive';
            }

            // Optionally handle the response here
            console.log(response);
            showToast(5000, response.status, response.message);
        },
        error: function(xhr, status, error) {
            // Handle any errors that occur during the AJAX request
            console.log(status, error);
        }
    });
}