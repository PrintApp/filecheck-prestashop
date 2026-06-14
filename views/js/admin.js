document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('filecheck-test-connection');
    if (!btn) {
        return;
    }
    
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        
        var result = document.getElementById('filecheck-connection-result');
        var pubKey = document.getElementById('filecheck_publishable_key').value;
        var secKey = document.getElementById('filecheck_secret_key').value;
        
        if (!pubKey || !secKey) {
            result.style.color = '#d63638';
            result.textContent = 'Please enter both Publishable and Secret keys.';
            return;
        }
        
        btn.disabled = true;
        btn.textContent = 'Testing...';
        result.style.color = '#666';
        result.textContent = 'Connecting...';
        
        var params = new URLSearchParams();
        params.append('publishable_key', pubKey);
        params.append('secret_key', secKey);
        
        fetch(ajax_test_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: params.toString()
        })
        .then(function(res) {
            if (!res.ok) {
                throw new Error('HTTP error ' + res.status);
            }
            return res.json();
        })
        .then(function(response) {
            btn.disabled = false;
            btn.textContent = 'Test Connection';
            if (response.success) {
                result.style.color = '#00a699';
                result.textContent = response.message;
            } else {
                result.style.color = '#d63638';
                result.textContent = response.message;
            }
        })
        .catch(function(err) {
            btn.disabled = false;
            btn.textContent = 'Test Connection';
            result.style.color = '#d63638';
            result.textContent = 'An error occurred during the request. Please check keys and try again.';
            console.error('[Filecheck Settings Error]', err);
        });
    });
});
