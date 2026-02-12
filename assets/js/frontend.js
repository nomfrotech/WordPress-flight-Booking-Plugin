(() => {
  if (typeof wfbpConfig === 'undefined') return;

  const app = document.querySelector('[data-wfbp-app]');
  const globalCurrencySelect = document.querySelector('[data-wfbp-global-currency]');
  const currencyStoreKey = 'wfbp_selected_currency';

  const currencies = Object.keys(wfbpConfig.currency.rates || {});
  const currentCurrency = () => localStorage.getItem(currencyStoreKey) || wfbpConfig.currency.display;

  function initCurrencySwitcher() {
    if (!globalCurrencySelect) return;

    currencies.forEach((currency) => {
      const option = document.createElement('option');
      option.value = currency;
      option.textContent = currency;
      option.selected = currency === currentCurrency();
      globalCurrencySelect.appendChild(option);
    });

    globalCurrencySelect.addEventListener('change', () => {
      localStorage.setItem(currencyStoreKey, globalCurrencySelect.value);
      window.dispatchEvent(new CustomEvent('wfbp:currencyChanged', { detail: globalCurrencySelect.value }));
    });
  }

  async function request(path, method = 'GET', body) {
    const response = await fetch(`${wfbpConfig.restBase}${path}`, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wfbpConfig.nonce,
      },
      body: body ? JSON.stringify(body) : undefined,
    });

    return response.json();
  }

  function renderTemplate(id, values) {
    const el = document.querySelector(id);
    if (!el) return '';
    let html = el.innerHTML;
    Object.entries(values).forEach(([key, value]) => {
      html = html.replaceAll(`{{${key}}}`, String(value));
    });
    return html;
  }

  function formatConverted(eur, currency) {
    const rate = wfbpConfig.currency.rates[currency] || 1;
    return (eur * rate).toFixed(2);
  }

  function mountAutocomplete(input, list) {
    let timer;

    input.addEventListener('focus', () => {
      if (!input.value) {
        list.innerHTML = `<div class="wfbp-suggest__hint">${wfbpConfig.i18n.airportHint}</div>`;
      }
    });

    input.addEventListener('input', () => {
      clearTimeout(timer);
      const keyword = input.value.trim();
      if (keyword.length < 2) {
        list.innerHTML = '';
        return;
      }

      timer = setTimeout(async () => {
        const json = await request(`/airports?q=${encodeURIComponent(keyword)}`);
        const items = Array.isArray(json.data) ? json.data : [];

        if (!items.length) {
          list.innerHTML = '<div class="wfbp-suggest__hint">No airports found.</div>';
          return;
        }

        list.innerHTML = items
          .map((airport) => {
            const code = airport.iata_code || airport.id || '';
            const city = airport.city_name || '';
            const name = airport.name || '';
            const country = airport.country_name || '';
            return `<button type="button" class="wfbp-suggest__item" data-iata="${code}"><strong>${code}</strong> ${city} - ${name}<small>${country}</small></button>`;
          })
          .join('');
      }, 200);
    });

    list.addEventListener('click', (event) => {
      const target = event.target.closest('[data-iata]');
      if (!target) return;
      input.value = target.getAttribute('data-iata');
      list.innerHTML = '';
    });

    document.addEventListener('click', (event) => {
      if (!event.target.closest('.wfbp-field')) {
        list.innerHTML = '';
      }
    });
  }

  function mountSearchFlow() {
    if (!app) return;

    const form = app.querySelector('[data-wfbp-form]');
    const results = app.querySelector('[data-wfbp-results]');
    const travelerBox = app.querySelector('[data-wfbp-traveler]');

    app.querySelectorAll('[data-airport-input]').forEach((input) => {
      const key = input.getAttribute('data-airport-input');
      const list = app.querySelector(`[data-airport-list="${key}"]`);
      if (list) mountAutocomplete(input, list);
    });

    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      const values = Object.fromEntries(new FormData(form).entries());
      const passengersCount = parseInt(values.passengers, 10) || 1;

      const offerRequestPayload = {
        data: {
          slices: [{
            origin: values.origin,
            destination: values.destination,
            departure_date: values.departure_date,
          }],
          passengers: Array.from({ length: passengersCount }, () => ({ type: 'adult' })),
          cabin_class: values.cabin_class,
        },
      };

      results.innerHTML = `<p>${wfbpConfig.i18n.loading}</p>`;
      travelerBox.hidden = true;
      travelerBox.innerHTML = '';

      const response = await request('/offers', 'POST', offerRequestPayload);
      const offers = Array.isArray(response?.data?.offers) ? response.data.offers : [];

      if (!offers.length) {
        results.innerHTML = `<p>${wfbpConfig.i18n.noOffers}</p>`;
        return;
      }

      const currency = currentCurrency();
      results.innerHTML = offers.slice(0, 8).map((offer) => {
        const eur = parseFloat(offer.total_amount || '0');
        const airline = offer?.owner?.name || 'Airline';

        return renderTemplate('#wfbp-offer-card-template', {
          airline,
          trip: `${values.origin} → ${values.destination}`,
          currency,
          converted: formatConverted(eur, currency),
          eur: eur.toFixed(2),
          offer_id: offer.id,
          total_eur: eur.toFixed(2),
        });
      }).join('');
    });

    results.addEventListener('click', (event) => {
      const trigger = event.target.closest('[data-select-offer]');
      if (!trigger) return;

      const offerId = trigger.getAttribute('data-select-offer');
      const totalEur = parseFloat(trigger.getAttribute('data-total-eur') || '0');
      const providersHtml = Object.entries(wfbpConfig.providers)
        .filter(([, cfg]) => cfg.enabled)
        .map(([provider]) => `<option value="${provider}">${provider.replace('_', ' ').toUpperCase()}</option>`)
        .join('');

      travelerBox.hidden = false;
      travelerBox.innerHTML = renderTemplate('#wfbp-traveler-template', { providers: providersHtml });

      const checkoutForm = travelerBox.querySelector('[data-wfbp-checkout]');
      checkoutForm.addEventListener('submit', async (submitEvent) => {
        submitEvent.preventDefault();
        const traveler = Object.fromEntries(new FormData(checkoutForm).entries());

        if (traveler.password !== traveler.password_confirm) {
          alert(wfbpConfig.i18n.passwordMismatch);
          return;
        }

        const customerResponse = await request('/customers', 'POST', {
          first_name: traveler.first_name,
          last_name: traveler.last_name,
          email: traveler.email,
          password: traveler.password,
        });

        if (customerResponse.error) {
          alert(customerResponse.error);
          return;
        }

        const orderResponse = await request('/orders', 'POST', {
          order: {
            type: 'instant',
            selected_offers: [offerId],
            passengers: [{
              type: 'adult',
              family_name: traveler.last_name,
              given_name: traveler.first_name,
              email: traveler.email,
              phone_number: traveler.phone,
            }],
          },
        });

        if (!orderResponse.order) {
          alert(orderResponse.error || 'Order could not be created.');
          return;
        }

        const localOrderId = orderResponse.order.meta?.local_order_id || 0;
        const checkoutResponse = await request('/checkout', 'POST', {
          local_order_id: localOrderId,
          total_eur: totalEur,
          provider: traveler.provider,
          currency: currentCurrency(),
        });

        if (checkoutResponse.error) {
          alert(checkoutResponse.error);
          return;
        }

        const checkout = checkoutResponse.checkout;
        if (checkout.provider === 'bank_transfer') {
          travelerBox.insertAdjacentHTML('beforeend', `<div class="wfbp-bank-box"><h4>Bank Transfer Instructions</h4><p>${checkout.instructions}</p><p><strong>Reference:</strong> ${checkout.reference}</p></div>`);
          return;
        }

        window.location.href = checkout.checkout_url;
      }, { once: true });
    });
  }

  initCurrencySwitcher();
  mountSearchFlow();
})();
