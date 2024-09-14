let ownGroupVoted = false;
let otherGroupVoted = false;
let ownGroupVotedEmployeeId = null;
let otherGroupVotedEmployeeId = null;

document.addEventListener('DOMContentLoaded', function () {

    // Check if the alert has been shown before
    const isAlertShown = localStorage.getItem('rulesAlertShown');

    if (!isAlertShown) {
        // Show the rules alert
        Swal.fire({
            title: 'Important Voting Rules',
            html: `
            <ul style="text-align: left;">
                <li><strong>Rule 1:</strong> You can only vote for someone from your own group.</li>
                <li><strong>Rule 2:</strong> You may choose only one person from across CDAC Kolkata.</li>
                <li><strong>Rule 3:</strong> Voting booths are mandatory for all participants.</li>
            </ul>
        `,
            icon: 'info',
            confirmButtonText: 'Got it!',
            width: '50%',  // Adjust the width
            padding: '1.5rem', // Customize padding,
            confirmButtonColor: '#3085d6',
            background: '#fefefe',
            customClass: {
                popup: 'custom-swal-popup',
                title: 'custom-swal-title',
                confirmButton: 'custom-swal-button'
            }
        }).then(() => {
            // After the alert is closed, set the flag in localStorage
            localStorage.setItem('rulesAlertShown', 'true');
        });
    }


    const voteButtons = document.querySelectorAll('.vote-btn');

    voteButtons.forEach(button => {
        button.addEventListener('click', function () {
            const employeeId = this.getAttribute('data-employee-id');
            const employeeName = this.getAttribute('data-employee-name');
            const votedImg = document.getElementById(`votedImg${employeeId}`);
            const voteButton = this;
            const isOwnGroup = voteButton.closest('.accordion-item').querySelector('.accordion-button').textContent.includes('Own Group');

            const hasVoted = votedImg.style.display === 'block';

            if (hasVoted) {
                // Remove the vote
                votedImg.style.display = 'none';
                voteButton.textContent = `Vote for ${employeeName}`;
                voteButton.classList.remove('btn-danger');
                voteButton.classList.add('btn-primary');

                if (isOwnGroup) {
                    ownGroupVoted = false;
                    ownGroupVotedEmployeeId = null;
                } else {
                    otherGroupVoted = false;
                    otherGroupVotedEmployeeId = null;
                }
            } else {
                // Check if vote limit is reached
                if (isOwnGroup) {
                    if (ownGroupVoted) {
                        showToast(5000, 'warning', 'You can only vote once in your own group.');
                        return;
                    }
                    ownGroupVoted = true;
                    ownGroupVotedEmployeeId = employeeId;
                } else {
                    if (otherGroupVoted) {
                        showToast(5000, 'warning', 'You can only vote once outside your own group.');
                        return;
                    }
                    otherGroupVoted = true;
                    otherGroupVotedEmployeeId = employeeId;
                }

                // Cast the vote
                votedImg.style.display = 'block';
                voteButton.textContent = `Remove vote for ${employeeName}`;
                voteButton.classList.remove('btn-primary');
                voteButton.classList.add('btn-danger');
            }
        });
    });
});

function submitVote() {
    if (ownGroupVoted && otherGroupVoted) {
        // Both votes have been cast, proceed with submission
        const formData = new FormData();
        formData.append('ownGroupVoted', ownGroupVoted);
        formData.append('otherGroupVoted', otherGroupVoted);
        formData.append('ownGroupVotedEmployeeId', ownGroupVotedEmployeeId);
        formData.append('otherGroupVotedEmployeeId', otherGroupVotedEmployeeId);

        fetch('/api/submitVote', {
            method: 'POST',
            body: formData,
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(5000, 'success', 'Your votes have been submitted successfully.');
                    setTimeout(function () {
                        window.location.href = '/voting'; // Replace with your desired URL
                    }, 5100);
                } else {
                    // Customize the error message based on the response
                    if (data.message.includes('Both votes are required')) {
                        showToast(5000, 'error', 'Please ensure both votes are selected before submitting.');
                    } else if (data.message.includes('Invalid votes or unauthorized attempt')) {
                        showToast(5000, 'error', 'The votes are invalid or unauthorized. Please try again.');
                    } else if (data.message.includes('The vote in your own group is invalid')) {
                        showToast(5000, 'error', 'The vote in your own group is invalid. Please check your selection.');
                    } else if (data.message.includes('The vote in another group is invalid')) {
                        showToast(5000, 'error', 'The vote in another group is invalid. Please check your selection.');
                    } else if (data.message.includes('Both votes cannot be in the same group')) {
                        showToast(5000, 'error', 'Both votes cannot be in the same group. Please vote for different groups.');
                    } else if (data.message.includes('You have already voted')) {
                        showToast(5000, 'error', 'You have already voted.');
                    } else {
                        showToast(5000, 'error', data.message || 'An error occurred while submitting your votes.');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast(5000, 'error', 'An unexpected error occurred.');
            });
    } else {
        showToast(5000, 'error', 'Please cast one vote in your own group and one in any other group before submitting.');
    }
}