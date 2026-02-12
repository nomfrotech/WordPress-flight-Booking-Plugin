(() => {
  const app = document.querySelector('[data-wfbp-app]');
  if (!app || typeof wfbpConfig === 'undefined') return;

  const form = app.querySelector('[data-wfbp-form]');
  const currencySelect = app.querySelector('[data-wfbp-currency]');
  const providerSelect = app.querySelector('[data-wfbp-provider]');
  const results = app.querySelector('[data-wfbp-results]');

  Object.keys(wfbpConfig.currency.rates).forEach((currency) => {
    const option = document.createElement('option');
    option.value = currency;
    option.textContent = currency;
    if (currency === wfbpConfig.currency.display) option.selected = true;
    currencySelect.appendChild(option);
  });

  Object.entries(wfbpConfig.providers).forEach(([provider, cfg]) => {
    if (!cfg.enabled) return;
    const option = document.createElement('option');
    option.value = provider;
    option.textContent = provider.replace('_', ' ').toUpperCase();
    providerSelect.appendChild(option);
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(form).entries());

    const payload = {
      data: {
        slices: [{ origin: data.origin, destination: data.destination, departure_date: data.departure_date }],
        passengers: [{ type: 'adult' }],
      }
    };

    results.innerHTML = '<p>Loading...</p>';

    const response = await fetch(`${wfbpConfig.restBase}/offers`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });

    const json = await response.json();
    if (!response.ok) {
      results.innerHTML = `<p>${json.error || 'Error searching flights'}</p>`;
      return;
    }

    const offers = (json.data && json.data.offers) ? json.data.offers : [];
    if (!offers.length) {
      results.innerHTML = '<p>No offers found.</p>';
      return;
    }

    const first = offers[0];
    const eur = parseFloat(first.total_amount || '0');
    const rate = wfbpConfig.currency.rates[data.currency] || 1;
    const converted = (eur * rate).toFixed(2);

    results.innerHTML = `
      <div class="wfbp-offer">
        <p><strong>Converted:</strong> ${data.currency} ${converted}</p>
        <p><strong>EUR Reference:</strong> EUR ${eur.toFixed(2)}</p>
        <button data-checkout="${first.id}">Checkout with ${data.provider.toUpperCase()}</button>
      </div>
    `;
  });
})();
