		<h1 id="titre">{_T string="Paypal Settings"}</h1>
		<form action="paypal_preferences.php" method="post" enctype="multipart/form-data">
{if !$paypal->isLoaded()}
            <div id="errorbox">
                <h1>{_T string="- ERROR -"}</h1>
            </div>
{/if}
{if $error_detected|@count != 0}
		<div id="errorbox">
			<h1>{_T string="- ERROR -"}</h1>
			<ul>
{foreach from=$error_detected item=error}
				<li>{$error}</li>
{/foreach}
			</ul>
		</div>
{/if}
{if $warning_detected|@count != 0}
		<div id="warningbox">
			<h1>{_T string="- WARNING -"}</h1>
			<ul>
{foreach from=$warning_detected item=warning}
				<li>{$warning}</li>
{/foreach}
			</ul>
		</div>
{/if}
{if $prefs_stored}
	<div id="infobox">{_T string="Paypal preferences has been saved."}</div>
{/if}
		<div class="bigtable">
			<fieldset class="cssform" id="general">
				<legend>{_T string="Paypal preferences:"}</legend>
				<p>
					<label for="paypal_id" class="bline required">{_T string="Paypal identifier:"}</label>
					<span class="tip">{_T string="Enter here one of your Paypal ID or email adress associated within your paypal account."}</span>
					<input type="text" name="paypal_id" id="paypal_id" value="{$paypal->getId()}"/>
				</p>
                <input type="hidden" name="valid" value="1"/>
{if $paypal->areAmountsLoaded() and $amounts|@count gt 0}
                <table>
                    <thead>
                        <tr>
                            <th>{_T string="Cotisation type"}</th>
                            <th>{_T string="Amount"}</th>
                            <th>{_T string="Inactive"}</th>
                        </tr>
                    </thead>
                    <tbody>
    {foreach from=$amounts key=k item=amount}
                        <tr>
                            <td>
                                <input type="hidden" name="amount_id[]" id="amount_id_{$k}" value="{$k}"/>
                                <label for="amount_{$k}">{$amount[0]}</label>
                            </td>
                            <td>
                                <input type="text" name="amounts[]" id="amount_{$k}" value="{$amount[2]|string_format:"%.2f"}"/>
                            </td>
                            <td>
                                <input type="checkbox" name="inactives[]" id="inactives_{$k}"{if $paypal->isInactive($k)} checked="checked"{/if}/>
                            </td>
                        </tr>
    {/foreach}
                    </tbody>
                </table>
			</fieldset>
{else}
            <p>{_T string="Error: no predefined amounts found."}</p>
{/if}

		</div>
		<div class="button-container">
			<input type="submit" class="submit" value="{_T string="Save"}"/>
		</div>
		<p>{_T string="NB : The mandatory fields are in"} <span class="required">{_T string="red"}</span></p>
		</form>
