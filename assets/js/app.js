"use strict";

const navToggle = document.querySelector('.nav-toggle');
const primaryNav = document.querySelector('.primary-nav');

if (navToggle && primaryNav) {
  navToggle.addEventListener('click', () => {
    const isOpen = primaryNav.classList.toggle('is-open');
    navToggle.classList.toggle('is-open', isOpen);
    navToggle.setAttribute('aria-expanded', String(isOpen));
  });
}

document.querySelectorAll('[data-dismiss-flash]').forEach((button) => {
  button.addEventListener('click', () => button.closest('.flash')?.remove());
});

document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    if (!window.confirm('Supprimer définitivement cet élément ? Cette opération est irréversible.')) {
      event.preventDefault();
    }
  });
});

document.querySelectorAll('[data-timeline-steps]').forEach((timeline) => {
  const steps = Number.parseInt(timeline.dataset.timelineSteps || '', 10);
  if (Number.isInteger(steps) && steps >= 1 && steps <= 10) {
    timeline.style.setProperty('--timeline-steps', String(steps));
  }
});

document.querySelectorAll('[data-chart-height]').forEach((bar) => {
  const height = Number.parseFloat(bar.dataset.chartHeight || '');
  if (Number.isFinite(height) && height >= 0 && height <= 100) {
    bar.style.setProperty('--chart-height', `${height}%`);
  }
});

document.querySelectorAll('[data-bar-width]').forEach((bar) => {
  const width = Number.parseFloat(bar.dataset.barWidth || '');
  if (Number.isFinite(width) && width >= 0 && width <= 100) {
    bar.style.setProperty('--bar-width', `${width}%`);
  }
});

window.setTimeout(() => {
  document.querySelectorAll('.flash').forEach((message) => {
    message.style.opacity = '0';
    window.setTimeout(() => message.remove(), 250);
  });
}, 5500);

const hero = document.querySelector('.home-hero');

if (hero) {
  const slides = Array.from(hero.querySelectorAll('.home-hero__slide'));
  const indicators = Array.from(hero.querySelectorAll('[data-hero-slide]'));
  const previousButton = hero.querySelector('[data-hero-previous]');
  const nextButton = hero.querySelector('[data-hero-next]');
  const status = hero.querySelector('[data-hero-status]');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let activeIndex = Math.max(slides.findIndex((slide) => slide.classList.contains('is-active')), 0);
  let autoplayTimer;

  const showSlide = (index) => {
    activeIndex = (index + slides.length) % slides.length;

    slides.forEach((slide, slideIndex) => {
      slide.classList.toggle('is-active', slideIndex === activeIndex);
    });

    indicators.forEach((indicator, indicatorIndex) => {
      const isActive = indicatorIndex === activeIndex;
      indicator.classList.toggle('is-active', isActive);
      indicator.setAttribute('aria-current', String(isActive));
    });

    if (status) {
      status.textContent = `Image ${activeIndex + 1} sur ${slides.length}`;
    }
  };

  const stopAutoplay = () => window.clearInterval(autoplayTimer);
  const startAutoplay = () => {
    stopAutoplay();
    if (!reducedMotion.matches && slides.length > 1) {
      autoplayTimer = window.setInterval(() => showSlide(activeIndex + 1), 6200);
    }
  };

  previousButton?.addEventListener('click', () => {
    showSlide(activeIndex - 1);
    startAutoplay();
  });

  nextButton?.addEventListener('click', () => {
    showSlide(activeIndex + 1);
    startAutoplay();
  });

  indicators.forEach((indicator, index) => {
    indicator.addEventListener('click', () => {
      showSlide(index);
      startAutoplay();
    });
  });

  hero.addEventListener('mouseenter', stopAutoplay);
  hero.addEventListener('mouseleave', startAutoplay);
  hero.addEventListener('focusin', stopAutoplay);
  hero.addEventListener('focusout', (event) => {
    if (!hero.contains(event.relatedTarget)) {
      startAutoplay();
    }
  });

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      stopAutoplay();
    } else {
      startAutoplay();
    }
  });

  reducedMotion.addEventListener?.('change', startAutoplay);
  startAutoplay();
}

const checkoutForm = document.querySelector('[data-checkout-form]');

if (checkoutForm) {
  const retrievalChoices = checkoutForm.querySelectorAll('[data-retrieval-choice]');
  const address = checkoutForm.querySelector('[data-delivery-address]');
  const addressInput = address?.querySelector('textarea');
  const deliveryZone = checkoutForm.querySelector('[data-delivery-zone]');
  const deliveryQuarter = checkoutForm.querySelector('[data-delivery-quarter]');
  const deliveryQuarterWrap = checkoutForm.querySelector('[data-delivery-quarter-wrap]');
  const deliveryZoneDescription = checkoutForm.querySelector('[data-delivery-zone-description]');
  const deliveryNotice = checkoutForm.querySelector('[data-checkout-zone-notice]');
  const delivery = checkoutForm.querySelector('[data-checkout-delivery]');
  const total = checkoutForm.querySelector('[data-checkout-total]');
  const productsTotal = Number.parseFloat(checkoutForm.dataset.productsTotal || '0') || 0;
  const formatMoney = (amount) => `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(amount)} FCFA`;

  const refreshCheckout = () => {
    const isDelivery = checkoutForm.querySelector('[data-retrieval-choice]:checked')?.value === 'LIVRAISON';
    const selectedZone = deliveryZone?.selectedOptions[0];
    const selectedZoneId = deliveryZone?.value || '';
    const deliveryFee = isDelivery ? (Number.parseFloat(selectedZone?.dataset.fee || '0') || 0) : 0;
    if (address) address.hidden = !isDelivery;
    if (addressInput) addressInput.required = isDelivery;
    if (deliveryZone) deliveryZone.required = isDelivery;
    if (deliveryQuarterWrap) deliveryQuarterWrap.hidden = !isDelivery;
    if (deliveryQuarter) {
      deliveryQuarter.querySelectorAll('option[data-zone]').forEach((option) => {
        const isAllowed = isDelivery && selectedZoneId !== '' && option.dataset.zone === selectedZoneId;
        option.hidden = !isAllowed;
        option.disabled = !isAllowed;
      });
      if (deliveryQuarter.selectedOptions[0]?.dataset.zone !== selectedZoneId) {
        deliveryQuarter.value = '';
      }
      deliveryQuarter.disabled = !isDelivery || selectedZoneId === '';
      deliveryQuarter.required = isDelivery;
    }
    if (deliveryNotice) deliveryNotice.hidden = !isDelivery;
    if (deliveryZoneDescription) {
      deliveryZoneDescription.textContent = selectedZone?.dataset.description || 'Le coût sera ajouté au total de votre commande.';
    }
    if (delivery) delivery.textContent = formatMoney(isDelivery ? deliveryFee : 0);
    if (total) total.textContent = formatMoney(productsTotal + (isDelivery ? deliveryFee : 0));
  };

  retrievalChoices.forEach((choice) => choice.addEventListener('change', refreshCheckout));
  deliveryZone?.addEventListener('change', refreshCheckout);
  refreshCheckout();
}
