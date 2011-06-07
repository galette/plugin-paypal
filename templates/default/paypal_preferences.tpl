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
					<input type="text" name="paypal_id" id="paypal_id" value="{$pref.paypal_id}"/>
				</p>
			</fieldset>
			<input type="hidden" name="valid" value="1"/>
		</div>
		<div class="button-container">
			<input type="submit" class="submit" value="{_T string="Save"}"/>
		</div>
		<p>{_T string="NB : The mandatory fields are in"} <span class="required">{_T string="red"}</span></p>
		</form>
