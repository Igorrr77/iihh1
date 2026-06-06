setTimeout(() => {
    if (window.location.search.includes('autorefresh=1')) {
        window.location.reload();
    }
}, 5000);
