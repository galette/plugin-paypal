        <h1 class="nojs">{_T string="Paypal" domain="paypal"}</h1>
        <ul>
            <li{if $cur_route eq "paypal_form" or $cur_route eq "paypal_success"} class="selected"{/if}><a href="{path_for name="paypal_form"}">{_T string="Payment form" domain="paypal"}</a></li>
{if $login->isAdmin() or $login->isStaff()}
            <li{if $cur_route eq "paypal_history"} class="selected"{/if}><a href="{path_for name="paypal_history"}">{_T string="Paypal History" domain="paypal"}</a></li>
            <li{if $cur_route eq "paypal_preferences"} class="selected"{/if}><a href="{path_for name="paypal_preferences"}">{_T string="Preferences"}</a></li>
{/if}
        </ul>
