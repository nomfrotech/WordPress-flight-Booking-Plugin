<div class="wfbp-search-wrap" data-wfbp-app>
  <form class="wfbp-search-form" data-wfbp-form>
    <div class="wfbp-row">
      <label><?php esc_html_e('From', 'wfbp'); ?> <input type="text" name="origin" required /></label>
      <label><?php esc_html_e('To', 'wfbp'); ?> <input type="text" name="destination" required /></label>
      <label><?php esc_html_e('Departure', 'wfbp'); ?> <input type="date" name="departure_date" required /></label>
    </div>
    <div class="wfbp-row">
      <label><?php esc_html_e('Currency', 'wfbp'); ?>
        <select name="currency" data-wfbp-currency></select>
      </label>
      <label><?php esc_html_e('Provider', 'wfbp'); ?>
        <select name="provider" data-wfbp-provider></select>
      </label>
      <button type="submit"><?php esc_html_e('Search Flights', 'wfbp'); ?></button>
    </div>
  </form>
  <div data-wfbp-results></div>
</div>
