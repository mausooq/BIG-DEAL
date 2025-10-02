<?php
// Small loader overlay component
?>
<style>
  /* Small Loader Overlay */
  #small-loader-overlay {
    position: fixed;
    inset: 0;
    background: rgba(255, 255, 255, 0.98);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 1;
    transition: opacity 300ms ease;
  }

  #small-loader-overlay.is-hidden {
    opacity: 0;
    pointer-events: none;
  }

  .small-loader-spinner {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: 3px solid rgba(204, 26, 26, 0.15);
    border-top-color: #cc1a1a;
    animation: smallLoaderSpin 0.9s linear infinite;
  }

  @keyframes smallLoaderSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }
</style>

<div id="small-loader-overlay" aria-live="polite" aria-busy="true" role="status">
  <div class="small-loader-spinner" aria-hidden="true"></div>
  <span class="visually-hidden" style="position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0;">Loading…</span>
  
</div>

<script>
  (function() {
    var overlay = document.getElementById('small-loader-overlay');
    if (!overlay) return;

    // Hide on full page load
    window.addEventListener('load', function() {
      requestAnimationFrame(function() {
        overlay.classList.add('is-hidden');
        // Remove from DOM after transition
        setTimeout(function() {
          if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
        }, 400);
      });
    });

    // Safety timeout in case load event is delayed
    setTimeout(function() {
      if (!document.readyState || document.readyState !== 'complete') return;
      if (!overlay) return;
      overlay.classList.add('is-hidden');
      setTimeout(function() {
        if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
      }, 400);
    }, 3000);
  })();
</script>

