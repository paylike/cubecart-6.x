# Cubecart plugin for Paylike

This plugin is *not* developed or maintained by Paylike but kindly made
available by a user.

Released under the GPL V3 license: https://opensource.org/licenses/GPL-3.0

## Supported Cubecart versions

*The plugin has been tested with most versions of Cubecart at every iteration. We recommend using the latest version of Cubecart, but if that is not possible for some reason, test the plugin with your Cubecart version and it would probably function properly.*

* Cubecart
 version last tested on: *6.2.6* 

## Installation

Once you have installed Cubecart, follow these simple steps:
  1. Signup at (paylike.io) [https://paylike.io] (it’s free)
  1. Create a live account
  1. Create an app key for your Cubecart website
  1. Upload the `Paylike_Payments` folder to the `modules\plugins` folder
  1. Insert Paylike API keys, from https://app.paylike.io to the extension settings page you can find under the available extensions section in your admin.
     
## Updating settings

Under the Paylike payment method settings, you can:
 * Update the payment method description in the payment gateways list
 * Update the title that shows up in the payment popup 
 * Add test/live keys
 * Set payment mode (test/live)
 * Change the capture type (Instant/Delayed) 
 
**Make sure to clear the cache after any setting update** 

## How to
  
1. Capture
* In Instant mode, the orders are captured automatically
* In delayed mode you can capture an order by changing its status to `Order Complete`
2. Refund
* To refund an order move you can use the the `Refund` tab which is available for all captured orders
3. Void
* All non captured orders will have a `Void` tab you can use for the void
   
     