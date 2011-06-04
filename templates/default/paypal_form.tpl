<h1 id="titre">{_T string="Paypal payment"}</h1>
<form action="https://www.sandbox.paypal.com/fr/cgi-bin/webscr" method="post">
<!--<form action="https://www.paypal.com/cgi-bin/webscr" method="post">--><!-- TODO: switch beetween dev/prod modes-->
    <!-- Paypal required variables -->
    <input type="hidden" name="cmd" value="_xclick"/>
    <input type="hidden" name="business" value="{$business}"/>
    <input type="hidden" name="lc" value="US"/><!-- notfound :( -->
    <input type="hidden" name="currency_code" value="EUR"/>{* FIXME: parameter? *}
    <input type="hidden" name="button_subtype" value="services"/>
    <input type="hidden" name="no_note" value="1"/>
    <input type="hidden" name="no_shipping" value="1"/>
    <input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynowCC_LG.gif:NonHostedGuest"/><!-- notfound :( -->
    <!-- Paypal dialogs -->
    <input type="hidden" name="return" value=""/><!-- TODO: -->
    <input type="hidden" name="rm" value="POST"/>{*Send POST values back to Galette after payment. Will be sent to return url above*}
    <input type="hidden" name="image_url" value=""/><!-- TODO: -->
    <input type="hidden" name="cancel_return" value=""/><!-- TODO: -->
    <input type="hidden" name="notify_url" value=""/><!-- TODO: -->

    <p>{_T string="Select an option below, then click 'Payment' to proceed. Once your paiment validated, an entry will be automatically added in the contributions table and staff members will receive a notification mail."}</p>

    <ul>
        <li>
            <input type="radio" name="item_name" id="in1" value="Personne physique (réduit)"/>
            <label for="in1">Personne physique (réduit) €10,00</label>
        </li>
        <li>
            <input type="radio" name="item_name" id="in2" checked="checked" value="Personne physique (normal)"/>
            <label for="in2">Personne physique (normal) €20,00</label>
        </li>
        <li>
            <input type="radio" name="item_name" id="in3" value="Personne physique (bienfaiteur)"/>
            <label for="in3">Personne physique (bienfaiteur) €80,00</label>
        </li>
        <li>
            <input type="radio" name="item_name" id="in4" value="Association"/>
            <label for="in4">Association €50,00</label>
        </li>
        <li>
            <input type="radio" name="item_name" id="in5" value="Entreprise"/>
            <label for="in5">Entreprise €300,00</label>
        </li>
        <li>
            <input type="radio" name="item_name" id="in6" value="Donation"/>
            <label for="in6">Don</label>
        </li>
    </ul>

    <p>{_T string="Enter an amount (with a minimum that correspond to the option you've selected below)."}</p>
    <label for="amount">{_T string="Amount"}</label>
    <input type="text" name="amount" id="amount" value="20.00"/>

    <div class="button-container">
        <input type="submit" class="submit" name="submit" value="{_T string="Payment"}"/>
    </div>
</form>
