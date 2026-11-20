{{-- Meta (Facebook) Pixel — active only when META_PIXEL_ID is set in .env --}}
@php($pixelId = preg_replace('/\D/', '', (string) config('services.meta_pixel_id')))
@if($pixelId)
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init','{{ $pixelId }}');fbq('track','PageView');
document.addEventListener('click',function(e){var a=e.target.closest('[data-track]');if(a)fbq('trackCustom','CTA_'+a.getAttribute('data-track'));});
document.addEventListener('submit',function(e){var t=e.target.getAttribute&&e.target.getAttribute('data-track-submit');if(t)fbq('track',t);},true);
</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $pixelId }}&ev=PageView&noscript=1" alt=""></noscript>
@endif
