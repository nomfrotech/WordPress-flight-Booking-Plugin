<script type="text/template" id="wfbp-traveler-template">
  <form class="wfbp-traveler-card" data-wfbp-checkout>
    <h3>Traveler & Account Information</h3>
    <p class="wfbp-note">Create account and continue to secure payment checkout.</p>
    <div class="wfbp-grid">
      <label class="wfbp-field">First Name<input name="first_name" required></label>
      <label class="wfbp-field">Last Name<input name="last_name" required></label>
      <label class="wfbp-field">Email<input type="email" name="email" required></label>
      <label class="wfbp-field">Phone<input name="phone" required></label>
      <label class="wfbp-field">Password<input type="password" name="password" required></label>
      <label class="wfbp-field">Confirm Password<input type="password" name="password_confirm" required></label>
    </div>
    <label class="wfbp-field">Payment Provider
      <select name="provider" required>{{providers}}</select>
    </label>
    <button type="submit" class="wfbp-btn-primary">Checkout</button>
  </form>
</script>
