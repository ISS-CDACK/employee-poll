document.addEventListener('DOMContentLoaded', function() {
    // Fetch data from the server using POST method
    fetch('/api/fetchGroupsData', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({}) // Empty body if no data needs to be sent
        })
        .then(response => response.json())
        .then(data => {
            // Prepare the data for the chart
            const groupNames = data.map(item => item.Group_Name);
            const totalUsers = data.map(item => item.Total_Users);
            const activeUsers = data.map(item => item.Active_Users);
            const inactiveUsers = data.map(item => item.Inactive_Users);

            // Update chart options
            var optionsProfileVisit = {
                annotations: {
                    position: "back",
                },
                dataLabels: {
                    enabled: false,
                },
                chart: {
                    type: "bar",
                    height: 300,
                },
                fill: {
                    opacity: 1,
                },
                plotOptions: {},
                series: [{
                    name: "Total Users",
                    data: totalUsers,
                }, {
                    name: "Active Users",
                    data: activeUsers,
                }, {
                    name: "Inactive Users",
                    data: inactiveUsers,
                }],
                colors: ["#435ebe", "#34c38f", "#f46a6a"],
                xaxis: {
                    categories: groupNames,
                },
            };

            var chartProfileVisit = new ApexCharts(
                document.querySelector("#chart-group-stat"),
                optionsProfileVisit
            );

            chartProfileVisit.render();
        })
        .catch(error => console.error('Error fetching data:', error));
});