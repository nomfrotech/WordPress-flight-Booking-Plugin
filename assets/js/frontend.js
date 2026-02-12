(() => {
  const app = document.querySelector('[data-wfbp-app]');
  const globalCurrencySelect = document.querySelector('[data-wfbp-global-currency]');
  const currencyStoreKey = 'wfbp_selected_currency';

  if (typeof wfbpConfig === 'undefined') return;

  const currencies = Object.keys(wfbpConfig.currency.rates || {});
  const selectedCurrency = localStorage.getItem(currencyStoreKey) || wfbpConfig.currency.display;

  if (globalCurrencySelect) {
    currencies.forEach((currency) => {
      const option = document.createElement('option');
      option.value = currency;
      option.textContent = currency;
      option.selected = currency === selectedCurrency;
      globalCurrencySelect.appendChild(option);
    });

    globalCurrencySelect.addEventListener('change', () => {
      localStorage.setItem(currencyStoreKey, globalCurrencySelect.value);
      window.dispatchEvent(new CustomEvent('wfbp:currencyChanged', { detail: globalCurrencySelect.value }));
    });
  }

  if (!app) return;

  const form = app.querySelector('[data-wfbp-form]');
  const results = app.querySelector('[data-wfbp-results]');
  const traveler = app.querySelector('[data-wfbp-traveler]');

  const activeCurrency = () => localStorage.getItem(currencyStoreKey) || wfbpConfig.currency.display;

  async function api(path, method = 'GET', body = null) {
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

  async function fetchAirports(keyword) {
    const response = await fetch(`${wfbpConfig.restBase}/airports?q=${encodeURIComponent(keyword)}`, {
      method: 'GET',
      headers: { 'X-WP-Nonce': wfbpConfig.nonce },
    });
    return response.json();
  }

  function mountAirportAutocomplete(input) {
    const key = input.getAttribute('data-airport-input');
    const list = app.querySelector(`[data-airport-list="${key}"]`);
    let timer;

    input.addEventListener('input', () => {
      clearTimeout(timer);
      const value = input.value.trim();
      if (value.length < 2) {
        list.innerHTML = '';
        return;
      }

      timer = setTimeout(async () => {
        const data = await fetchAirports(value);
        const items = Array.isArray(data.data) ? data.data : [];
        list.innerHTML = items.map((airport) => {
          const code = airport.iata_code || airport.id || '';
          const label = `${airport.city_name || ''} - ${airport.name || ''} (${code})`;
          return `<button type="button" class="wfbp-suggest__item" data-value="${code}">${label}</button>`;
        }).join('');
      }, 250);
    });

    list.addEventListener('click', (event) => {
      const target = event.target.closest('[data-value]');
      if (!target) return;
      input.value = target.getAttribute('data-value');
      list.innerHTML = '';
    });
  }

  app.querySelectorAll('[data-airport-input]').forEach((input) => mountAirportAutocomplete(input));

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const data = Object.fromEntries(new FormData(form).entries());
    const passengers = parseInt(data.passengers, 10) || 1;

    const payload = {
      data: {
        slices: [{ origin: data.origin, destination: data.destination, departure_date: data.departure_date }],
        passengers: Array.from({ length: passengers }, () => ({ type: 'adult' })),
        cabin_class: data.cabin_class,
      }
    };

    results.innerHTML = `<p>${wfbpConfig.i18n.loading}</p>`;
    traveler.hidden = true;

    const json = await api('/offers', 'POST', payload);
    const offers = (json.data && json.data.offers) ? json.data.offers : [];

    if (!offers.length) {
      results.innerHTML = `<p>${wfbpConfig.i18n.noOffers}</p>`;
      return;
    }

    const currency = activeCurrency();
    const rate = wfbpConfig.currency.rates[currency] || 1;

    results.innerHTML = offers.slice(0, 6).map((offer) => {
      const eur = parseFloat(offer.total_amount || '0');
      const converted = (eur * rate).toFixed(2);
      const owner = offer.owner?.name || 'Airline';
      return `
        <article class="wfbp-offer-card">
          <h3>${owner}</h3>
          <p><strong>${currency} ${converted}</strong></p>
          <p>Reference: EUR ${eur.toFixed(2)}</p>
          <button type="button" class="wfbp-btn-secondary" data-select-offer="${offer.id}" data-total-eur="${eur}">${wfbpConfig.i18n.selectFlight}</button>
        </article>
      `;
    }).join('');
  });

  results.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-select-offer]');
    if (!btn) return;

    const offerId = btn.getAttribute('data-select-offer');
    const totalEur = parseFloat(btn.getAttribute('data-total-eur') || '0');
    const providers = Object.entries(wfbpConfig.providers).filter(([, config]) => config.enabled);

    traveler.hidden = false;
    traveler.innerHTML = `
      <form class="wfbp-traveler-card" data-wfbp-checkout>
        <h3>Traveler Information</h3>
        <div class="wfbp-grid">
          <label class="wfbp-field">First Name<input name="first_name" required></label>
          <label class="wfbp-field">Last Name<input name="last_name" required></label>
          <label class="wfbp-field">Email<input type="email" name="email" required></label>
          <label class="wfbp-field">Password<input type="password" name="password" required></label>
        </div>
        <label class="wfbp-field">Payment Provider
          <select name="provider" required>
            ${providers.map(([provider]) => `<option value="${provider}">${provider.replace('_', ' ').toUpperCase()}</option>`).join('')}
          </select>
        </label>
        <button type="submit" class="wfbp-btn-primary">${wfbpConfig.i18n.checkout}</button>
      </form>
    `;

    const checkoutForm = traveler.querySelector('[data-wfbp-checkout]');
    checkoutForm.addEventListener('submit', async (submitEvent) => {
      submitEvent.preventDefault();
      const formData = Object.fromEntries(new FormData(checkoutForm).entries());

      const orderPayload = {
        order: {
          type: 'instant',
          selected_offers: [offerId],
          passengers: [{
            type: 'adult',
            family_name: formData.last_name,
            given_name: formData.first_name,
            email: formData.email,
          }],
        },
      };

      const orderResponse = await api('/orders', 'POST', orderPayload);
      if (!orderResponse.order) {
        alert(orderResponse.error || 'Could not create order');
        return;
      }

      const localOrderId = orderResponse.order.meta?.local_order_id || 0;
      const checkoutResponse = await api('/checkout', 'POST', {
        local_order_id: localOrderId,
        total_eur: totalEur,
        provider: formData.provider,
        currency: activeCurrency(),
      });

      if (checkoutResponse.error) {
        alert(checkoutResponse.error);
        return;
      }

      const checkout = checkoutResponse.checkout;
      if (checkout.provider === 'bank_transfer') {
        alert(`Bank transfer instructions: ${checkout.instructions}\nReference: ${checkout.reference}`);
        return;
      }

      window.location.href = checkout.checkout_url;
    });
  });
})();
