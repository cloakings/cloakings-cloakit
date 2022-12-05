jQuery(function ($) {
    $(document).ready(function() {
        $('.enable-on-page-select select').select2({
            placeholder: "Select page",
            allowClear: true
        });
    });
});
