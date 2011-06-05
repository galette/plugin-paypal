<h1 id="titre">{_T string="Paypal payment"}</h1>
<form action="https://www.sandbox.paypal.com/fr/cgi-bin/webscr" method="post" id="paypal">
<!--<form action="https://www.paypal.com/cgi-bin/webscr" method="post" id="paypal">--><!-- TODO: switch beetween dev/prod modes-->
    <!-- Paypal required variables -->
    <input type="hidden" name="cmd" value="_xclick"/>
    <input type="hidden" name="business" value="{$business}"/>{* FIXME: use paypal ID insted of mail adress. alos, main asso adress may differ from paypal adress *}
    <input type="hidden" name="lc" value="FR"/>{*language of the login or sign-up page*}{* FIXME: parameter *}
    <input type="hidden" name="currency_code" value="EUR"/>{*transaction currency*}{* FIXME: parameter? *}
    <input type="hidden" name="button_subtype" value="services"/>
    <input type="hidden" name="no_note" value="1"/>
    <input type="hidden" name="no_shipping" value="1"/>
    <input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynowCC_LG.gif:NonHostedGuest"/><!-- notfound :( -->
    <input type="hidden" name="item_number " value=""/><!-- TODO: -->
    <!-- Paypal dialogs -->
    <input type="hidden" name="return" value="{$plugin_url}paypal_success.php"/>
    <input type="hidden" name="rm" value="POST"/>{*Send POST values back to Galette after payment. Will be sent to return url above*}
    <input type="hidden" name="image_url" value="{$galette_url}picture.php?logo=true"/>
    <input type="hidden" name="cancel_return" value="{$plugin_url}paypal_form.php?cancelled=true"/>
    <input type="hidden" name="notify_url" value="{$plugin_url}paypal_notify.php"/>
    <input type="hidden" name="cbt" value="{_T string="Go back to %s Website to complete your inscription."}"/>

    <p>{_T string="Select an option below, then click 'Payment' to proceed. Once your paiment validated, an entry will be automatically added in the contributions table and staff members will receive a notification mail."}</p>

{if $amounts|@count gt 0}
    <ul>
    {foreach from=$amounts key=k item=amount}
        <li>
            <input type="radio" name="item_name" id="in{$k}" value="{$amount[0]}"/>
            <label for="in{$k}">{$amount[0]}
        {if $amount[2] gt 0}
                (<span id="in{$k}_amount">{$amount[2]|string_format:"%.2f"}</span> €)
        {/if}
            </label>
        </li>
    {/foreach}
    </ul>
{else}
    <p>{_T string="No predefined amounts have been defined yet."}</p>
{/if}

    <p>{_T string="Enter an amount."}
{if $amounts|@count gt 0}
        <br/><span class="required">{_T string="WARNING: If you enter an amount below, make sure that it is not lower than the amount of the option you've selected."}</span>
{/if}
    </p>
    <label for="amount">{_T string="Amount"}</label>
    <input type="text" name="amount" id="amount" value="20.00"/>

    <div class="button-container">
        <input type="submit" class="submit" name="submit" value="{_T string="Payment"}"/>
    </div>
</form>
<script type="text/javascript">
    //<![CDATA[
    $(function() {ldelim}
        $('input[name="item_name"]').click(function(){ldelim}
            var _amount = parseFloat($('#' + this.id + '_amount').text());
            var _current_amount = parseFloat($('#amount').val());
            if ( _amount != '' && _current_amount < _amount ) {ldelim}
                $('#amount').val(_amount);
            {rdelim}
        {rdelim});

{if $amounts|@count gt 0}
        $('#paypal').submit(function(){ldelim}
            var _checked = $('input:checked');
            if (_checked.length == 0 ) {ldelim}
                alert("{_T string="You have to select an option"}");
                return false;
            {rdelim} else {ldelim}
                var _current_amount = parseFloat($('#amount').val());
                var _amount = parseFloat($('#' + _checked[0].id + '_amount').text());
                if ( _amount != NaN && _current_amount < _amount ) {ldelim}
                    alert("{_T string="The amount you've entered is lower than the minimum amount for the selected option.\\nPlease choose another option or change the amount."}");
                    return false;
                {rdelim}
            {rdelim}
            return true;
        {rdelim});
{/if}
    {rdelim});
    //]]>
</script>
