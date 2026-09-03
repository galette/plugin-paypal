---
title: ஆவணமாக்கல்
description: Paypal integration
---

> **Warning**
> 
> PayPal is discontinuing its legacy Website Payments Standard (WPS)
> integration, with full transaction processing termination scheduled for
> January 2027. This plugin is **not compatible** with any other integration.

இந்த சொருகி வழங்குகிறது:

* ஒரு தொகையை ஒரு பங்களிப்பு வகையுடன் இணைப்பதற்கான சாத்தியம்,
* பேபால் கட்டண படிவத்தை உருவாக்கவும்,
* ஒரு வரலாறு,
* தானியங்கி பரிவர்த்தனை சேமிப்பு ஒரு முறை சரிபார்க்கப்பட்டது.

![Paypal plugin preferences](images/preferences.png)

![Paypal payment form](images/form.png)

![Paypal payment form (public)](images/public_form.png)

> **Warning**
> 
> பேபால் கொடுப்பனவுகள் கையாளப்படும் விதம் காரணமாக, குறிப்பாக கட்டண
> உறுதிப்படுத்தல், உங்கள் நிகழ்வு பகிரங்கமாக அணுகக்கூடியதாக இருக்க வேண்டும்.

## நிறுவல்

முதலில், சொருகி பதிவிறக்கவும்:

* [Get latest Paypal
  plugin!](https://github.com/galette-plugins/plugin-paypal/releases/latest)
* [Get Paypal plugin nightly
  build!](https://github.com/galette-plugins/plugin-paypal/releases/tag/nightly)

பதிவிறக்கம் செய்யப்பட்ட காப்பகத்தைக் கேலட் `செருகுநிரல்கள்` கோப்பகத்தில்
பிரித்தெடுக்கவும். எடுத்துக்காட்டாக, லினக்சின் கீழ் (`{url}` மற்றும் `{version}`
ஆகியவற்றை சரியான மதிப்புகளுடன் மாற்றுகிறது):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-paypal-{version}.tar.bz2
```

## தரவுத்தள துவக்கம்

வேலை செய்ய, இந்தச் சொருகி தரவுத்தளத்தில் பல அட்டவணைகள் தேவை. காண்க [கேலட்
செருகுநிரல்கள் மேலாண்மை
இடைமுகம்](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

இது முடிந்தது; பேபால் சொருகி நிறுவப்பட்டுள்ளது :)

## சொருகி உள்ளமைவு

சொருகி நிறுவப்பட்டதும், சில புதிய உள்ளீடுகளுடன் `பேபால்` குழு பட்டியலில்
சேர்க்கப்படுகிறது:

* `கட்டண படிவம்`: கட்டண படிவமே, இது ஒரு பொது பக்கமாக அணுகக்கூடியது,
* `விருப்பத்தேர்வுகள்`: சொருகி விருப்பத்தேர்வுகள், நிர்வாகிகள் மற்றும்
  ஊழியர்களுக்கு அணுகக்கூடியவை.

சரியாக வேலை செய்ய, நீங்கள் மிக முக்கியமான மதிப்பை நிரப்ப வேண்டும்: உங்கள் பேபால்
கணக்குக் குறியீடு. உங்கள் பேபால் கணக்குடன் தொடர்புடைய மின்னஞ்சல் முகவரியை
நீங்கள் பயன்படுத்தலாம் (ஆனால் பேபால் மாற்றினால் அதை கேலட்டில் மாற்ற வேண்டும்)
அல்லது உங்கள் வணிகர் அடையாளங்காட்டி. உங்கள் வணிகர் அடையாளம் காண, பேபாலில்
உள்நுழைக, அதை உங்கள் கணக்கு விருப்பங்களில் காண்பீர்கள். அடையாளங்காட்டியை
மாற்றுவது நிர்வாகிகளுக்கு மட்டுமே அனுமதிக்கப்படுகிறது.

பங்களிப்பு வகைகள் தொடர்பான தொகையைத் திருத்தவும், சில வகைகளை மறைக்கவும்
விருப்பத்தேர்வுகள் திரை அனுமதிக்கிறது.

After that, any user can choose the contribution type, adjust the amount and pay
from his Paypal account. If the user is a logged in member, and if the
contribution type is a membership extension, its membership will be recalculated
when the payment will be confirmed.
