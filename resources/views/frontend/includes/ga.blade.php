
@if (config('setting.ga_tracking_id'))
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('setting.ga_tracking_id') }}"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', config('setting.ga_tracking_id'));
  </script>
@endif