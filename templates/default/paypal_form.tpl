{if !$paypal->isLoaded()}
<div id="errorbox">
    <h1>{_T string="- ERROR -"}</h1>
    <p>{_T string="<strong>Payment coult not work</strong>: An error occured (that has been logged) while loading Paypal preferences from database.<br/>Please report the issue to the staff."}</p>
    <p>{_T string="Our apologies for the annoyance :("}</p>
</div>
{else}
    {if !$paypal->areAmountsLoaded()}
<div id="warningbox">
    <h1>{_T string="- WARNING -"}</h1>
    <p>{_T string="Predefined amounts cannot be loaded, that is not a critical error."}</p>
</div>
    {/if}
    <section>
<form action="{if GALETTE_MODE eq 'DEV'}https://www.sandbox.paypal.com/fr/cgi-bin/webscr{else}https://www.paypal.com/cgi-bin/webscr{/if}" method="post" id="paypal">
    <!-- Paypal required variables -->
    {if $custom}
    <input type="hidden" name="custom" value="{$custom}"/>
    {/if}
    <input type="hidden" name="cmd" value="_xclick"/>
    <input type="hidden" name="business" value="{$paypal->getId()}"/>
    <input type="hidden" name="lc" value="FR"/>{*language of the login or sign-up page*}{* FIXME: parameter *}
    <input type="hidden" name="currency_code" value="EUR"/>{*transaction currency*}{* FIXME: parameter? *}
    <input type="hidden" name="button_subtype" value="services"/>
    <input type="hidden" name="no_note" value="1"/>
    <input type="hidden" name="no_shipping" value="1"/>
    {*<input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynowCC_LG.gif:NonHostedGuest"/><!-- notfound :( -->*}
    <input type="hidden" name="item_name" id="item_name" value="{_T string="annual fee"}"/>
    <!-- Paypal dialogs -->
    <input type="hidden" name="return" value="{$plugin_url}paypal_success.php"/>
    <input type="hidden" name="rm" value="POST"/>{*Send POST values back to Galette after payment. Will be sent to return url above*}
    <input type="hidden" name="image_url" value="{$galette_url}picture.php?logo=true"/>
    <input type="hidden" name="cancel_return" value="{$plugin_url}paypal_form.php?cancelled=true"/>
    <input type="hidden" name="notify_url" value="{$plugin_url}paypal_notify.php"/>
    <input type="hidden" name="cbt" value="{_T string="Go back to %s Website to complete your inscription."}"/>

    <p>{_T string="Select an option below, then click 'Payment' to proceed.<br/>Once your paiment validated, an entry will be automatically added in the contributions table and staff members will receive a notification mail."}</p>

    {if $paypal->areAmountsLoaded() and $amounts|@count gt 0}
    <div id="amounts">
        {foreach from=$amounts key=k item=amount name=amounts}
        <input type="radio" name="item_number" id="in{$k}" value="{$k}"{if $smarty.foreach.amounts.index == 0} checked="checked"{/if}/>
        <label for="in{$k}"><span id="in{$k}_name">{$amount[0]}</span>
        {if $amount[2] gt 0}
            (<span id="in{$k}_amount">{$amount[2]|string_format:"%.2f"}</span> €)
        {/if}
        </label>
        {/foreach}
    </div>
    {else}
    <p>{_T string="No predefined amounts have been defined yet."}</p>
    {/if}

    <p>{_T string="Enter an amount."}
    {if $paypal->areAmountsLoaded() and $amounts|@count gt 0}
        <br/><span class="required">{_T string="WARNING: If you enter an amount below, make sure that it is not lower than the amount of the option you've selected."}</span>
    {/if}
    </p>
    <p>
        <label for="amount">{_T string="Amount"}</label>
        <input type="text" name="amount" id="amount" value="{if $amounts|@count > 0}{$amounts[1][2]}{else}20{/if}"/>
    </p>

    <div class="button-container">
        <input type="submit" name="submit" value="{_T string="Payment"}"/>
    </div>
</form>
        </section>
<script type="text/javascript">
    $(function() {ldelim}
        $('input[name="item_number"]').change(function(){ldelim}
            var _amount = parseFloat($('#' + this.id + '_amount').text());
            var _name = $('#' + this.id + '_name').text();
            $('#item_name').val(_name);
            if ( _amount != '' && !isNaN(_amount) ) {ldelim}
                $('#amount').val(_amount);
            {rdelim}
        {rdelim});

        $('#amounts').buttonset();

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
</script>
{/if}
