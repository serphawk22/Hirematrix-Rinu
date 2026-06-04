(function () {
    function getConfig() {
        var root = document.getElementById('recruiterJobsPage');
        return root ? root.dataset : {};
    }

    function csrfData(config) {
        var data = {};
        if (config.csrfName && config.csrfHash) {
            data[config.csrfName] = config.csrfHash;
        }
        return data;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var $ = window.jQuery;
        if (!$ || !document.getElementById('recruiterJobsPage')) {
            return;
        }

        window.updateApplicationStatus = function (applicationId, newStatus) {
            var config = getConfig();
            if (!confirm('Are you sure you want to change the status of this application to ' + newStatus + '?')) {
                return;
            }

            $.ajax({
                url: config.statusUrlBase + applicationId,
                type: 'POST',
                data: Object.assign({ status: newStatus }, csrfData(config)),
                dataType: 'json',
                success: function (response) {
                    if (response.csrf_hash) {
                        config.csrfHash = response.csrf_hash;
                    }
                    if (response.status === 'success') {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function (xhr) {
                    alert('An error occurred: ' + xhr.responseText);
                    console.error(xhr.responseText);
                }
            });
        };
    });
})();
