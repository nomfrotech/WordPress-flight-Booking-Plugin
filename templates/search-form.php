<section class="wfbp-booking" data-wfbp-app>
  <div class="wfbp-booking__head">
    <h2><?php esc_html_e('Search Flights', 'wfbp'); ?></h2>
    <p><?php esc_html_e('Find fares, choose your flight, complete traveler details, then checkout securely.', 'wfbp'); ?></p>
  </div>

  <form class="wfbp-search-card" data-wfbp-form autocomplete="off">
    <div class="wfbp-grid">
      <label class="wfbp-field"><?php esc_html_e('From', 'wfbp'); ?>
        <input type="text" name="origin" data-airport-input="origin" placeholder="Lagos (LOS)" required />
        <div class="wfbp-suggest" data-airport-list="origin"></div>
      </label>

      <label class="wfbp-field"><?php esc_html_e('To', 'wfbp'); ?>
        <input type="text" name="destination" data-airport-input="destination" placeholder="London (LHR)" required />
        <div class="wfbp-suggest" data-airport-list="destination"></div>
      </label>

      <label class="wfbp-field"><?php esc_html_e('Departure', 'wfbp'); ?>
        <input type="date" name="departure_date" required />
      </label>

      <label class="wfbp-field"><?php esc_html_e('Trip Type', 'wfbp'); ?>
        <select name="trip_type">
          <option value="oneway"><?php esc_html_e('One Way', 'wfbp'); ?></option>
          <option value="roundtrip"><?php esc_html_e('Round Trip', 'wfbp'); ?></option>
        </select>
      </label>

      <label class="wfbp-field"><?php esc_html_e('Passengers', 'wfbp'); ?>
        <input type="number" min="1" max="9" name="passengers" value="1" required />
      </label>

      <label class="wfbp-field"><?php esc_html_e('Cabin', 'wfbp'); ?>
        <select name="cabin_class">
          <option value="economy"><?php esc_html_e('Economy', 'wfbp'); ?></option>
          <option value="premium_economy"><?php esc_html_e('Premium Economy', 'wfbp'); ?></option>
          <option value="business"><?php esc_html_e('Business', 'wfbp'); ?></option>
          <option value="first"><?php esc_html_e('First', 'wfbp'); ?></option>
        </select>
      </label>
    </div>

    <button type="submit" class="wfbp-btn-primary"><?php esc_html_e('Search Available Flights', 'wfbp'); ?></button>
  </form>

  <div class="wfbp-results" data-wfbp-results></div>
  <div class="wfbp-traveler" data-wfbp-traveler hidden></div>
</section>
