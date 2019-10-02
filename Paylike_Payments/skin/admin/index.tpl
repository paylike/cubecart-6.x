<?php
/**
 * CubeCart v6
 * ========================================
 * CubeCart is a registered trade mark of CubeCart Limited
 * Copyright CubeCart Limited 2014. All rights reserved.
 * UK Private Limited Company No. 5323904
 * ========================================
 * Web:   http://www.cubecart.com
 * Email:  sales@devellion.com
 * License:  GPL-2.0 http://opensource.org/licenses/GPL-2.0
 */
?>
<form action="{$VAL_SELF}" method="post" enctype="multipart/form-data">
  <input type="hidden" name="module[name]" value="Paylike">
  <div id="Paylike_Form" class="tab_content">
	<h3>{$TITLE}</h3>
	<fieldset><legend>{$LANG.module.config_settings}</legend>
	  <div><label for="status">{$LANG.common.status}</label><span><input type="hidden" name="module[status]" id="status" class="toggle" value="{$MODULE.status}" /></span></div>
	  <div><label for="position">{$LANG.module.position}</label><span><input type="text" name="module[position]" id="position" class="textbox number" value="{$MODULE.position}" /></span></div>
	  <div>
          <label for="scope">{$LANG.module.scope}</label>
          <span>
              <select name="module[scope]">
                      <option value="both" {$SELECT_scope_both}>{$LANG.module.both}</option>
                      <option value="main" {$SELECT_scope_main}>{$LANG.module.main}</option>
                      <option value="mobile" {$SELECT_scope_mobile}>{$LANG.module.mobile}</option>
                  </select>
          </span>
      </div>
	  <div><label for="default">{$LANG.common.default}</label><span><input type="hidden" name="module[default]" id="default" class="toggle" value="{$MODULE.default}" /></span></div>

	</fieldset>

	<fieldset><legend>{$LANG.paylike_text.setup_paylike}</legend>
      <div>
          <label for="scope">{$LANG.paylike_text.mode}</label>
          <span>
              <select name="module[mode]">
                  <option value="test" {$SELECT_mode_test}>{$LANG.paylike_text.test}</option>
                  <option value="live" {$SELECT_mode_live}>{$LANG.paylike_text.live}</option>
              </select>
          </span>
      </div>
      <div><label for="testkey_public">{$LANG.paylike_text.testkey_public}</label><span><input type="text" name="module[testkey_public]" value="{$MODULE.testkey_public}" class="textbox" size="30" /></span></div>
      <div><label for="testkey_app">{$LANG.paylike_text.testkey_app}</label><span><input type="text" name="module[testkey_app]" value="{$MODULE.testkey_app}" class="textbox" size="30" /></span></div>
      <div><label for="livekey_public">{$LANG.paylike_text.livekey_public}</label><span><input type="text" name="module[livekey_public]" value="{$MODULE.livekey_public}" class="textbox" size="30" /></span></div>
      <div><label for="livekey_app">{$LANG.paylike_text.livekey_app}</label><span><input type="text" name="module[livekey_app]" value="{$MODULE.livekey_app}" class="textbox" size="30" /></span></div>
      <div>
          <label for="scope">{$LANG.paylike_text.capturemode}</label>
          <span>
              <select name="module[capturemode]">
                  <option value="instant" {$SELECT_capturemode_instant}>{$LANG.paylike_text.instant}</option>
                  <option value="delayed" {$SELECT_capturemode_delayed}>{$LANG.paylike_text.delayed}</option>
              </select>
          </span>
      </div>
      <div><label for="description">{$LANG.common.description} ? ({$LANG.paylike_text.textcheckout})</label><span><input name="module[desc]" id="description" class="textbox" type="text" value="{$MODULE.desc}" /></span></div>
      <div><label for="paydescription">{$LANG.paylike_text.paydescription}</label><span><input type="text" name="module[paydescription]" value="{$MODULE.paydescription}" class="textbox" size="30" /></span></div>
	</fieldset>
    
    
	<p>{$LANG.module.description_options}</p>
  </div>
  {$MODULE_ZONES}
  <div class="form_control"><input type="submit" name="save" value="{$LANG.common.save}" /></div>
  <input type="hidden" name="token" value="{$SESSION_TOKEN}" />
</form>
