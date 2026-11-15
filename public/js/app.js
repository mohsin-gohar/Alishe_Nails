document.addEventListener('DOMContentLoaded', function () {
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  function showToast(message, type) {
    var toast = document.createElement('div');
    toast.className = 'site-toast' + (type === 'error' ? ' site-toast--error' : '');
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    toast.textContent = message;
    document.body.appendChild(toast);
    requestAnimationFrame(function () { toast.classList.add('is-visible'); });
    setTimeout(function () {
      toast.classList.remove('is-visible');
      setTimeout(function () { toast.remove(); }, 250);
    }, 3200);
  }

  function updateCartCount(count) {
    document.querySelectorAll('.navbar__cart-count').forEach(function (element) {
      element.textContent = count;
    });
  }

  function bindImageFallbacks() {
    document.querySelectorAll('[data-image-fallback]').forEach(function (image) {
      if (image.dataset.fallbackBound) return;
      image.dataset.fallbackBound = 'true';
      image.addEventListener('error', function () {
        var placeholder = document.createElement('div');
        placeholder.className = 'img-placeholder';
        placeholder.innerHTML = 'Alishe Nails<br>Image unavailable';
        image.replaceWith(placeholder);
      });
    });
  }

  function bindWishlist() {
    document.querySelectorAll('[data-wishlist-id]').forEach(function (button) {
      if (button.dataset.wishlistBound) return;
      button.dataset.wishlistBound = 'true';

      // Guests get a link to the login page — let it navigate normally.
      if (button.tagName === 'A') return;

      button.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        var form = button.closest('form');
        var id = button.dataset.wishlistId;
        if (!form || !id || button.dataset.wishlistLoading === 'true') return;

        button.dataset.wishlistLoading = 'true';
        button.disabled = true;

        fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken || '',
          },
        })
          .then(function (response) {
            return response.json().then(function (data) {
              if (!response.ok) throw new Error(data.message || 'Unable to update your wishlist.');
              return data;
            });
          })
          .then(function (data) {
            // Keep every card for this product in sync (it can appear in
            // more than one section on the same page).
            document.querySelectorAll('[data-wishlist-id="' + id + '"]').forEach(function (twin) {
              twin.setAttribute('aria-pressed', data.saved ? 'true' : 'false');
              twin.setAttribute('aria-label', data.saved ? 'Remove item from wishlist' : 'Add item to wishlist');
              var icon = twin.querySelector('i');
              if (icon) icon.className = data.saved ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
            });
            showToast(data.message);
          })
          .catch(function (error) { showToast(error.message, 'error'); })
          .finally(function () {
            button.dataset.wishlistLoading = 'false';
            button.disabled = false;
          });
      });
    });
  }

  function bindShopAjax() {
    var shopLayout = document.querySelector('.shop-layout');
    if (!shopLayout || shopLayout.dataset.ajaxBound) return;
    shopLayout.dataset.ajaxBound = 'true';

    function loadShop(url, pushHistory) {
      shopLayout.classList.add('is-loading');
      return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (response) {
          if (!response.ok) throw new Error('Shop request failed');
          return response.text();
        })
        .then(function (html) {
          var parsed = new DOMParser().parseFromString(html, 'text/html');
          var nextLayout = parsed.querySelector('.shop-layout');
          if (!nextLayout) throw new Error('Shop content unavailable');
          shopLayout.innerHTML = nextLayout.innerHTML;
          if (pushHistory) window.history.pushState({}, '', url);
          bindShopAjax();
          bindWishlist();
          bindImageFallbacks();
        })
        .catch(function () { showToast('Unable to update the collection. Please try again.', 'error'); })
        .finally(function () { shopLayout.classList.remove('is-loading'); });
    }

      shopLayout.loadShop = loadShop;

    shopLayout.addEventListener('submit', function (event) {
      var form = event.target.closest('[data-shop-form]');
      if (!form) return;
      event.preventDefault();
      loadShop(form.action + '?' + new URLSearchParams(new FormData(form)).toString(), true);
    });

    shopLayout.addEventListener('click', function (event) {
      var link = event.target.closest('.pagination a, .filter-chip__remove');
      if (!link) return;
      event.preventDefault();
      loadShop(link.href, true);
    });
  }

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('form[action*="/cart/add/"]');
    if (!form || form.closest('.shop-layout') === null) return;
    event.preventDefault();

    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken || '',
      },
    })
      .then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok) throw new Error(data.message || 'Unable to add item');
          return data;
        });
      })
      .then(function (data) {
        updateCartCount(data.cart_count);
        showToast(data.message);
      })
      .catch(function (error) { showToast(error.message, 'error'); });
  });

  window.addEventListener('popstate', function () {
    var shopLayout = document.querySelector('.shop-layout');
    if (shopLayout && shopLayout.loadShop) shopLayout.loadShop(window.location.href, false);
  });

  bindImageFallbacks();
  bindWishlist();
  bindShopAjax();

  // ---------- Mobile navbar toggle ----------
  var navbar = document.querySelector('.navbar');
  var navToggle = document.querySelector('.navbar__toggle');
  if (navToggle && navbar) {
    var setNavState = function (open) {
      navbar.classList.toggle('is-open', open);
      navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      navToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    };

    navToggle.setAttribute('aria-expanded', 'false');
    navToggle.setAttribute('aria-controls', 'primary-navigation');
    navbar.querySelector('.navbar__mobile-links').id = 'primary-navigation';

    navToggle.addEventListener('click', function () {
      setNavState(!navbar.classList.contains('is-open'));
    });

    // Close on Escape for keyboard users.
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && navbar.classList.contains('is-open')) {
        setNavState(false);
        navToggle.focus();
      }
    });

    // Reset state when resizing back to desktop.
    window.addEventListener('resize', function () {
      if (window.innerWidth > 1024 && navbar.classList.contains('is-open')) {
        setNavState(false);
      }
    });
  }

  // ---------- Shop: mobile filters toggle ----------
  var filtersToggle = document.querySelector('[data-filters-toggle]');
  var filtersPanel = document.querySelector('.filters');
  if (filtersToggle && filtersPanel) {
    filtersToggle.addEventListener('click', function () {
      filtersPanel.classList.toggle('is-open');
    });
  }

  // ---------- Product media gallery (images + videos, ordered) ----------
  var mediaSlides = document.querySelectorAll('[data-media-slide]');

  function pauseInactiveVideos() {
    mediaSlides.forEach(function (slide) {
      if (slide.classList.contains('is-active')) return;
      var video = slide.querySelector('video');
      if (video) video.pause();
    });
  }

  document.querySelectorAll('[data-media-thumb]').forEach(function (thumb) {
    thumb.addEventListener('click', function () {
      mediaSlides.forEach(function (slide) { slide.classList.remove('is-active'); });
      document.querySelectorAll('[data-media-thumb]').forEach(function (t) {
        t.classList.remove('is-active');
      });

      var target = document.querySelector('[data-media-slide][data-index="' + thumb.dataset.index + '"]');
      if (target) target.classList.add('is-active');
      thumb.classList.add('is-active');
      pauseInactiveVideos();
    });
  });

  var mediaStage = document.querySelector('[data-media-stage]');
  if (mediaStage) {
    mediaStage.addEventListener('click', function (event) {
      // Click-to-zoom stays image-only; never hijack clicks on video controls.
      if (event.target.tagName === 'VIDEO' || event.target.closest('video')) return;
      mediaStage.classList.toggle('is-zoomed');
    });
    mediaStage.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      if (event.target.tagName === 'VIDEO' || event.target.closest('video')) return;
      event.preventDefault();
      mediaStage.classList.toggle('is-zoomed');
    });
  }

  // ---------- Accessible sizing and content modals ----------
  var openModal = function (id) {
    var modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    var closeButton = modal.querySelector('.modal__close');
    if (closeButton) closeButton.focus();
  };
  var closeModal = function (modal) {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };
  document.querySelectorAll('[data-modal-open]').forEach(function (button) {
    button.addEventListener('click', function () { openModal(button.dataset.modalOpen); });
  });
  document.querySelectorAll('[data-modal-close]').forEach(function (button) {
    button.addEventListener('click', function () { closeModal(button.closest('[data-modal]')); });
  });
  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    var modal = document.querySelector('[data-modal].is-open');
    if (modal) closeModal(modal);
  });

  // ---------- Option pills (shape / size & custom mm logic) ----------
  var customSizeBox = document.querySelector('#custom-size-box');
  var customMmInputs = document.querySelectorAll('.custom-mm-input');

  function getCustomSizeString() {
    var vals = [];
    customMmInputs.forEach(function (inp) {
      vals.push(inp.dataset.finger + ':' + (inp.value || '0') + 'mm');
    });
    return 'Custom (' + vals.join(', ') + ')';
  }

  document.querySelectorAll('[data-option-group]').forEach(function (group) {
    var pills = group.querySelectorAll('.option-pill');
    var hiddenInput = group.querySelector('input[type=hidden]');
    pills.forEach(function (pill) {
      pill.addEventListener('click', function () {
        pills.forEach(function (p) { p.classList.remove('is-selected'); });
        pill.classList.add('is-selected');

        var val = pill.dataset.value;
        if (group.id === 'size-option-group' && customSizeBox) {
          if (val === 'Custom') {
            customSizeBox.classList.add('is-active');
            val = getCustomSizeString();
          } else {
            customSizeBox.classList.remove('is-active');
          }
        }

        if (hiddenInput) hiddenInput.value = val;
        updateProductWhatsAppAndSubtotal();
      });
    });
  });

  if (customMmInputs.length > 0) {
    customMmInputs.forEach(function (inp) {
      inp.addEventListener('input', function () {
        var sizeInput = document.querySelector('#product-size-input');
        var customPill = document.querySelector('#size-option-group .option-pill[data-value="Custom"].is-selected');
        if (sizeInput && customPill) {
          sizeInput.value = getCustomSizeString();
          updateProductWhatsAppAndSubtotal();
        }
      });
    });
  }

  // ---------- Quantity selector (product page + cart) ----------
  document.querySelectorAll('[data-qty-selector]').forEach(function (selector) {
    var input = selector.querySelector('input');
    var min = parseInt(input.min || '1', 10);
    var max = parseInt(input.max || '20', 10);

    selector.querySelectorAll('button').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var value = parseInt(input.value || '1', 10);
        value = btn.dataset.action === 'increase' ? value + 1 : value - 1;
        value = Math.max(min, Math.min(max, value));
        input.value = value;
        updateProductWhatsAppAndSubtotal();

        if (selector.dataset.autoSubmit === 'true') {
          selector.closest('form').requestSubmit();
        }
      });
    });

    input.addEventListener('input', function () {
      updateProductWhatsAppAndSubtotal();
    });
  });

  // ---------- Live Product Subtotal & Dynamic WhatsApp URL (thekunuz style) ----------
  function updateProductWhatsAppAndSubtotal() {
    var qtyInput = document.querySelector('#product-qty-input');
    var subtotalEl = document.querySelector('#product-live-subtotal');
    var waBtn = document.querySelector('#btn-product-whatsapp');
    if (!qtyInput || !subtotalEl || !waBtn) return;

    var qty = parseInt(qtyInput.value || '1', 10);
    var unitPrice = parseFloat(subtotalEl.dataset.unitPrice || '0');
    var total = qty * unitPrice;

    subtotalEl.textContent = 'PKR ' + total.toLocaleString();

    var productName = waBtn.dataset.productName || 'Nail Set';
    var waBase = waBtn.dataset.waBase || 'https://wa.me/923113925166';
    var productUrl = waBtn.dataset.productUrl || window.location.href;

    var shapeInput = document.querySelector('input[name="shape"]');
    var sizeInput = document.querySelector('input[name="size"]');
    var shapeVal = shapeInput ? shapeInput.value : '';
    var sizeVal = sizeInput ? sizeInput.value : '';

    var lines = [
      'Assalam-o-Alaikum! Mujhe Alishe Nails se ye product order karna hai:',
      '',
      '*Product:* ' + productName,
    ];
    if (shapeVal) lines.push('*Shape & Length:* ' + shapeVal);
    if (sizeVal) lines.push('*Size:* ' + sizeVal);
    lines.push('*Quantity:* ' + qty);
    lines.push('*Total Price:* PKR ' + total.toLocaleString());
    lines.push('*Link:* ' + productUrl);
    lines.push('');
    lines.push('Baraye meharbani availability confirm karein. Shukriya!');

    waBtn.href = waBase + '?text=' + encodeURIComponent(lines.join('\n'));
  }
  updateProductWhatsAppAndSubtotal();

  // ---------- Payment method selection ----------
  document.querySelectorAll('.payment-option').forEach(function (option) {
    option.addEventListener('click', function () {
      var radio = option.querySelector('input[type="radio"]');
      if (radio && !radio.checked) {
        radio.checked = true;
        updateCheckoutPaymentMethods();
      }
    });
  });

  // ---------- Checkout shipping and payment details ----------
  var checkoutCity = document.querySelector('#city');
  var checkoutArea = document.querySelector('#area');
  var shippingAmount = document.querySelector('#checkout-shipping-amount');
  var totalAmount = document.querySelector('#checkout-total-amount');
  var submitBtnTotal = document.querySelector('#checkout-btn-total');

  function updateCheckoutPaymentMethods() {
    var selectedPayment = document.querySelector('input[name="payment_method"]:checked');
    if (!selectedPayment) return;

    var val = selectedPayment.value;

    // Highlight selected payment card
    document.querySelectorAll('.payment-option').forEach(function (opt) {
      var radio = opt.querySelector('input[type="radio"]');
      if (radio && radio.checked) {
        opt.classList.add('is-selected');
      } else {
        opt.classList.remove('is-selected');
      }
    });

    // Toggle wallet detail boxes
    var jazzBox = document.querySelector('[data-wallet-box="jazzcash"]');
    var sadaBox = document.querySelector('[data-wallet-box="sadapay"]') || document.querySelector('[data-wallet-box="easypaisa"]');
    var refInput = document.querySelector('#transaction_reference');

    if (jazzBox) jazzBox.style.display = (val === 'jazzcash' || val === 'jazzcash_easypaisa' || val === 'jazzcash_sadapay') ? 'block' : 'none';
    if (sadaBox) sadaBox.style.display = (val === 'sadapay' || val === 'easypaisa') ? 'block' : 'none';

    if (refInput) {
      refInput.required = true;
      if (val === 'jazzcash') {
        refInput.placeholder = 'e.g. 10492840284 (From 8558 SMS / JazzCash App)';
      } else if (val === 'sadapay' || val === 'easypaisa') {
        refInput.placeholder = 'e.g. TID / Reference from SadaPay receipt';
      }
    }
  }

  function updateCheckoutShipping() {
    if (!checkoutCity || !checkoutArea || !shippingAmount || !totalAmount) return;

    var params = new URLSearchParams({
      city: checkoutCity.value,
      area: checkoutArea.value,
    });

    fetch('/checkout/shipping-fee?' + params.toString(), {
      headers: { 'Accept': 'application/json' },
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        var fee = Number(data.shipping);
        shippingAmount.textContent = 'PKR ' + fee.toLocaleString();
        totalAmount.textContent = 'PKR ' + Number(data.total).toLocaleString();
        if (submitBtnTotal) {
          submitBtnTotal.textContent = 'PKR ' + Number(data.total).toLocaleString();
        }
      })
      .catch(function () {
        // Keep the server-rendered amounts if the live estimate is unavailable.
      });
  }

  if (checkoutCity && checkoutArea) {
    checkoutCity.addEventListener('change', updateCheckoutShipping);
    checkoutArea.addEventListener('change', updateCheckoutShipping);
  }

  var paymentRadios = document.querySelectorAll('input[name="payment_method"]');
  paymentRadios.forEach(function (radio) {
    radio.addEventListener('change', updateCheckoutPaymentMethods);
  });
  updateCheckoutPaymentMethods();

  // Prevent double submission on checkout form
  var checkoutForm = document.querySelector('#checkout-form');
  var checkoutSubmitBtn = document.querySelector('#btn-place-order');
  if (checkoutForm && checkoutSubmitBtn) {
    checkoutForm.addEventListener('submit', function () {
      checkoutSubmitBtn.disabled = true;
      checkoutSubmitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing Order...';
    });
  }

  // Copy number button handler
  document.querySelectorAll('.btn-copy-number').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var num = this.getAttribute('data-copy-target');
      if (!num) return;

      var self = this;
      var origHtml = self.innerHTML;

      var onCopied = function () {
        self.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
        setTimeout(function () { self.innerHTML = origHtml; }, 2000);
      };

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(num).then(onCopied).catch(function () {});
      } else {
        var tempInput = document.createElement('input');
        tempInput.value = num;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        onCopied();
      }
    });
  });

  // ---------- Accordion chevrons on product page ----------
  document.querySelectorAll('.accordion-item').forEach(function (item) {
    item.addEventListener('toggle', function () {
      var icon = item.querySelector('.chevron');
      if (icon) icon.style.transform = item.open ? 'rotate(180deg)' : 'rotate(0deg)';
    });
  });

  // ---------- Navbar search toggle ----------
  var searchToggle = document.querySelectorAll('[data-search-toggle]');
  var searchForm = document.querySelector('[data-search-form]');
  var searchClose = document.querySelector('[data-search-close]');

  searchToggle.forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!searchForm) return;
      var isOpen = searchForm.style.display !== 'none';
      searchForm.style.display = isOpen ? 'none' : 'flex';
      if (!isOpen) {
        var input = searchForm.querySelector('input[type=text]');
        if (input) input.focus();
      }
    });
  });

  if (searchClose && searchForm) {
    searchClose.addEventListener('click', function () {
      searchForm.style.display = 'none';
    });
  }

  // ---------- Navbar account menu dropdown ----------
  var accountToggle = document.querySelector('[data-account-toggle]');
  var accountMenu = document.querySelector('[data-account-menu]');
  if (accountToggle && accountMenu) {
    accountToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      accountMenu.style.display = accountMenu.style.display === 'none' ? 'block' : 'none';
    });
    document.addEventListener('click', function (e) {
      if (!accountMenu.contains(e.target) && e.target !== accountToggle) {
        accountMenu.style.display = 'none';
      }
    });
  }

  // ---------- Auto-dismiss alerts ----------
  document.querySelectorAll('.alert').forEach(function (alertBox) {
    setTimeout(function () {
      alertBox.style.transition = 'opacity .4s ease';
      alertBox.style.opacity = '0';
      setTimeout(function () { alertBox.remove(); }, 400);
    }, 4000);
  });
});
