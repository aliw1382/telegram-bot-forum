<?php


define(
    'KEY_SHARE_NUMBER', telegram()->buildKeyBoard( [
    [
        telegram()->buildKeyboardButton( '📱 اشتراک گذاری شماره همراهم', true )
    ]
], 'برای اشتراک گذاری بر روی دکمه زیر کلیک کنید' )
);


define(
    'KEY_START_MENU', telegram()->buildKeyBoard( [

    [
        telegram()->buildKeyboardButton( '👤 پروفایل' ),
        telegram()->buildKeyboardButton( '👮‍♀️ ورود به حساب کاربری 👮‍♀️' ),
    ],
    [
        telegram()->buildKeyboardButton( '🔔 رویداد ها' ),
        telegram()->buildKeyboardButton( '📔 مطالب مفید' ),
    ],
    [
        telegram()->buildKeyboardButton( '📚 جزوه های اساتید' ),
        telegram()->buildKeyboardButton( '🔗 لینک های مفید' ),
    ],
    [
        telegram()->buildKeyboardButton('🍔 سامانه رزرو غذا 🍟')
    ],
    [
        telegram()->buildKeyboardButton( '💌 ارتباط با ما 📨' ),
        telegram()->buildKeyboardButton( '📜 درباره ما' ),
    ]


], 'منوی اصلی' )
);

define(
    'KEY_ADMIN_START_MENU', telegram()->buildKeyBoard( [
    [
        telegram()->buildKeyboardButton( '🔃 برگشت به پنل کاربر 🔄' )
    ],
    [
        telegram()->buildKeyboardButton( '🎓 مدیریت دانشجویان' ),
        telegram()->buildKeyboardButton( '🛎 مدیریت رویداد ها' ),
    ],
    [
        telegram()->buildKeyboardButton( '📃 مدیریت فرم ها ✉️' ),
        telegram()->buildKeyboardButton( '📨 مدیریت فایل ها 📬' ),
    ],
    [
        telegram()->buildKeyboardButton( '🤝 مدیریت مدیران' ),
        telegram()->buildKeyboardButton( '📮 ارسال پیام' ),
    ],
    [
        telegram()->buildKeyboardButton( '📝 مدیریت پیام های ربات' ),
        telegram()->buildKeyboardButton( '🖍 مدیریت منو' ),
    ],
    [
        telegram()->buildKeyboardButton( '📊 آمار ربات 📊' )
    ]
], 'منوی ادمین' )
);

define(
    'KEY_BACK_TO_MENU', telegram()->buildKeyBoard( [
    [
        telegram()->buildKeyboardButton( '▶️ برگشت به منو اصلی' )
    ]
] )
);

define(
    'KEY_CANCEL_MENU', telegram()->buildKeyBoard( [
    [
        telegram()->buildKeyboardButton( '⛔️ انصراف' )
    ]
] )
);

