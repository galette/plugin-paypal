<h1 id="titre">Paiement Paypal</h1>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">

    <input type="hidden" name="cmd" value="_xclick-subscriptions"/>
    <input type="hidden" name="return" value="current galette URL/paypal.page.php"/>
    <input type="hidden" name="rm" value="POST"/>{*Send POST values back to Galette after payment. Will be sent to return url above*}
    <input type="hidden" name="currency_code" value="EUR"/>{*FIXME: parameter?*}
    <input type="hidden" name="no_shipping" value="1"/>
    <input type="hidden" name="image_url" value="https://www.designerfotos.com/logo.gif"/>
    <input type="hidden" name="return" value="http://www.designerfotos.com/thankyou.htm"/>
    <input type="hidden" name="cancel_return" value="http://www.designerfotos.com/cancel.htm"/>
    <!-- N’invite pas le client à ajouter une remarque à son achat -->
    <input type="hidden" name="no_note" value="1"/>
    <input type="hidden" name="business" value="{$business}"/>

  {*<input type="hidden" name="hosted_button_id" value="SVT2GEPYFVT84">*}
  {*<input type="hidden" name="lc" value="US"/><!-- notfound -->
  <input type="hidden" name="src" value="1"/><!-- notfound -->
  <input type="hidden" name="bn" value="PP-SubscriptionsBF:btn_subscribe_LG.gif:NonHostedGuest"/><!-- notfound -->
  <input type="hidden" name="on0" value="Abonnement"/>
  <input type="hidden" name="option_select0" value="Personne physique (réduit)"/>
  <input type="hidden" name="option_amount0" value="10.00"/>
  <input type="hidden" name="option_period0" value="Y"/>
  <input type="hidden" name="option_frequency0" value="1"/>
  <input type="hidden" name="option_select1" value="Personne physique (normal)"/>
  <input type="hidden" name="option_amount1" value="20.00"/>
  <input type="hidden" name="option_period1" value="Y"/>
  <input type="hidden" name="option_frequency1" value="1"/>
  <input type="hidden" name="option_select2" value="Personne physique (bienfaiteur)"/>
  <input type="hidden" name="option_amount2" value="80.00"/>
  <input type="hidden" name="option_select3" value="Association"/>
  <input type="hidden" name="option_amount3" value="50.00"/>
  <input type="hidden" name="option_select4" value="Entreprise"/>
  <input type="hidden" name="option_amount4" value="300.00"/>
  <input type="hidden" name="option_index" value="0"/>*}

    <p>{_T string="Select an option below, then click 'Pay' to proceed. Once your paiment validated, an entry will be automatically added in the contributions table and staff members will receive a notification mail."}</p>

    <ul>
        <li>
            <input type="radio" name="item_name" id="in1"/>
            <label for="in1">Personne physique (réduit) €10,00</label>
        </li>
        <li>
            <input type="radio" name="item_name" id="in2" selected="selected"/>
            <label for="in2">Personne physique (normal) €20,00</label>
        </li>
        <li>
            <input type="radio" name="item_name" id="in3"/>
            <label for="in3">Personne physique (bienfaiteur) €80,00</label>
        </li>
        <li>
            <input type="radio" name="item_name" id="in4"/>
            <label for="in4">Association €50,00</label>
        </li>
        <li>
            <input type="radio" name="item_name" id="in5"/>
            <label for="in5">Entreprise €300,00</label>
        </li>
        <li>
            <input type="radio" name="item_name" id="in6" />
            <label for="in6">{_T string="Donation"}</label>
        </li>
    </ul>

    <label for="amount">{_T string="Amount"}</label>
    <input type="text" name="amount" id="amount" value="20"/>

    <div class="button-container">
        <input type="submit" class="submit" name="submit" value="{_T string="Proceed"}"/>
    </div>
</form>
