<script type="text/template" id="wfbp-offer-card-template">
  <article class="wfbp-offer-card">
    <div class="wfbp-offer-card__header">
      <h3>{{airline}}</h3>
      <span>{{trip}}</span>
    </div>
    <div class="wfbp-offer-card__body">
      <p class="wfbp-price">{{currency}} {{converted}}</p>
      <p class="wfbp-reference">EUR {{eur}}</p>
    </div>
    <button type="button" class="wfbp-btn-secondary" data-select-offer="{{offer_id}}" data-total-eur="{{total_eur}}">Select this Flight</button>
  </article>
</script>
