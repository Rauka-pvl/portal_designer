<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute өрісі қабылдануы керек.',
    'accepted_if' => ':attribute өрісі :other :value болғанда қабылдануы керек.',
    'active_url' => ':attribute өрісі жарамды URL болуы керек.',
    'after' => ':attribute өрісі :date күнінен кейінгі күн болуы керек.',
    'after_or_equal' => ':attribute өрісі :date күніне тең немесе одан кейінгі күн болуы керек.',
    'alpha' => ':attribute өрісі тек әріптерден тұруы керек.',
    'alpha_dash' => ':attribute өрісі тек әріптерден, сандардан, сызықшалардан және астын сызу белгілерінен тұруы керек.',
    'alpha_num' => ':attribute өрісі тек әріптерден және сандардан тұруы керек.',
    'any_of' => ':attribute өрісі жарамсыз.',
    'array' => ':attribute өрісі массив болуы керек.',
    'ascii' => ':attribute өрісі тек бір байттық әріп-сандық таңбалар мен таңбалардан тұруы керек.',
    'before' => ':attribute өрісі :date күнінен бұрынғы күн болуы керек.',
    'before_or_equal' => ':attribute өрісі :date күніне тең немесе одан бұрынғы күн болуы керек.',
    'between' => [
        'array' => ':attribute өрісі :min және :max элементтер арасында болуы керек.',
        'file' => ':attribute өрісі :min және :max килобайт арасында болуы керек.',
        'numeric' => ':attribute өрісі :min және :max арасында болуы керек.',
        'string' => ':attribute өрісі :min және :max таңба арасында болуы керек.',
    ],
    'boolean' => ':attribute өрісі true немесе false болуы керек.',
    'can' => ':attribute өрісі рұқсат етілмеген мән қамтиды.',
    'confirmed' => ':attribute өрісінің растауы сәйкес келмейді.',
    'contains' => ':attribute өрісі міндетті мәннен ажыратылған.',
    'current_password' => 'Құпия сөз дұрыс емес.',
    'date' => ':attribute өрісі жарамды күн болуы керек.',
    'date_equals' => ':attribute өрісі :date күніне тең күн болуы керек.',
    'date_format' => ':attribute өрісі :format форматына сәйкес келуі керек.',
    'decimal' => ':attribute өрісі :decimal ондық таңбаларға ие болуы керек.',
    'declined' => ':attribute өрісі бас тартылуы керек.',
    'declined_if' => ':attribute өрісі :other :value болғанда бас тартылуы керек.',
    'different' => ':attribute және :other өрістері әртүрлі болуы керек.',
    'digits' => ':attribute өрісі :digits цифрдан тұруы керек.',
    'digits_between' => ':attribute өрісі :min және :max цифр арасында болуы керек.',
    'dimensions' => ':attribute өрісі жарамсыз сурет өлшемдеріне ие.',
    'distinct' => ':attribute өрісі қайталанатын мәнге ие.',
    'doesnt_contain' => ':attribute өрісі келесілердің ешқайсысын қамтуы тиіс емес: :values.',
    'doesnt_end_with' => ':attribute өрісі келесілердің біреуімен аяқталуы тиіс емес: :values.',
    'doesnt_start_with' => ':attribute өрісі келесілердің біреуінен басталуы тиіс емес: :values.',
    'email' => ':attribute өрісі жарамды электрондық пошта мекенжайы болуы керек.',
    'encoding' => ':attribute өрісі :encoding кодталуы керек.',
    'ends_with' => ':attribute өрісі келесілердің біреуімен аяқталуы керек: :values.',
    'enum' => 'Таңдалған :attribute жарамсыз.',
    'exists' => 'Таңдалған :attribute жарамсыз.',
    'extensions' => ':attribute өрісі келесі кеңейтулердің біреуіне ие болуы керек: :values.',
    'file' => ':attribute өрісі файл болуы керек.',
    'filled' => ':attribute өрісі мәнге ие болуы керек.',
    'gt' => [
        'array' => ':attribute өрісі :value элементтерден көп болуы керек.',
        'file' => ':attribute өрісі :value килобайттан үлкен болуы керек.',
        'numeric' => ':attribute өрісі :value мәнінен үлкен болуы керек.',
        'string' => ':attribute өрісі :value таңбадан көп болуы керек.',
    ],
    'gte' => [
        'array' => ':attribute өрісі :value элемент немесе одан көп болуы керек.',
        'file' => ':attribute өрісі :value килобайтқа тең немесе одан үлкен болуы керек.',
        'numeric' => ':attribute өрісі :value мәніне тең немесе одан үлкен болуы керек.',
        'string' => ':attribute өрісі :value таңбаға тең немесе одан көп болуы керек.',
    ],
    'hex_color' => ':attribute өрісі жарамды он алтылық түс болуы керек.',
    'image' => ':attribute өрісі сурет болуы керек.',
    'in' => 'Таңдалған :attribute жарамсыз.',
    'in_array' => ':attribute өрісі :other ішінде болуы керек.',
    'in_array_keys' => ':attribute өрісі келесі кілттердің кем дегенде біреуін қамтуы керек: :values.',
    'integer' => ':attribute өрісі бүтін сан болуы керек.',
    'ip' => ':attribute өрісі жарамды IP мекенжайы болуы керек.',
    'ipv4' => ':attribute өрісі жарамды IPv4 мекенжайы болуы керек.',
    'ipv6' => ':attribute өрісі жарамды IPv6 мекенжайы болуы керек.',
    'json' => ':attribute өрісі жарамды JSON жолы болуы керек.',
    'list' => ':attribute өрісі тізім болуы керек.',
    'lowercase' => ':attribute өрісі кіші әріппен жазылуы керек.',
    'lt' => [
        'array' => ':attribute өрісі :value элементтерден аз болуы керек.',
        'file' => ':attribute өрісі :value килобайттан кіші болуы керек.',
        'numeric' => ':attribute өрісі :value мәнінен кіші болуы керек.',
        'string' => ':attribute өрісі :value таңбадан аз болуы керек.',
    ],
    'lte' => [
        'array' => ':attribute өрісі :value элементтен көп болмауы керек.',
        'file' => ':attribute өрісі :value килобайтқа тең немесе одан кіші болуы керек.',
        'numeric' => ':attribute өрісі :value мәніне тең немесе одан кіші болуы керек.',
        'string' => ':attribute өрісі :value таңбадан көп болмауы керек.',
    ],
    'mac_address' => ':attribute өрісі жарамды MAC мекенжайы болуы керек.',
    'max' => [
        'array' => ':attribute өрісі :max элементтен көп болмауы керек.',
        'file' => ':attribute өрісі :max килобайттан үлкен болмауы керек.',
        'numeric' => ':attribute өрісі :max мәнінен үлкен болмауы керек.',
        'string' => ':attribute өрісі :max таңбадан көп болмауы керек.',
    ],
    'max_digits' => ':attribute өрісі :max цифрдан көп болмауы керек.',
    'mimes' => ':attribute өрісі келесі түрдегі файл болуы керек: :values.',
    'mimetypes' => ':attribute өрісі келесі түрдегі файл болуы керек: :values.',
    'min' => [
        'array' => ':attribute өрісі кем дегенде :min элементтен тұруы керек.',
        'file' => ':attribute өрісі кем дегенде :min килобайт болуы керек.',
        'numeric' => ':attribute өрісі кем дегенде :min болуы керек.',
        'string' => ':attribute өрісі кем дегенде :min таңбадан тұруы керек.',
    ],
    'min_digits' => ':attribute өрісі кем дегенде :min цифрдан тұруы керек.',
    'missing' => ':attribute өрісі жоқ болуы керек.',
    'missing_if' => ':attribute өрісі :other :value болғанда жоқ болуы керек.',
    'missing_unless' => ':attribute өрісі :other :value болмағанда жоқ болуы керек.',
    'missing_with' => ':attribute өрісі :values болғанда жоқ болуы керек.',
    'missing_with_all' => ':attribute өрісі :values болғанда жоқ болуы керек.',
    'multiple_of' => ':attribute өрісі :value еселігі болуы керек.',
    'not_in' => 'Таңдалған :attribute жарамсыз.',
    'not_regex' => ':attribute өрісінің форматы жарамсыз.',
    'numeric' => ':attribute өрісі сан болуы керек.',
    'password' => [
        'letters' => ':attribute өрісі кем дегенде бір әріптен тұруы керек.',
        'mixed' => ':attribute өрісі кем дегенде бір бас әріптен және бір кіші әріптен тұруы керек.',
        'numbers' => ':attribute өрісі кем дегенде бір цифрдан тұруы керек.',
        'symbols' => ':attribute өрісі кем дегенде бір таңбадан тұруы керек.',
        'uncompromised' => 'Берілген :attribute деректердің утечкасында пайда болды. Басқа :attribute таңдаңыз.',
    ],
    'present' => ':attribute өрісі болуы керек.',
    'present_if' => ':attribute өрісі :other :value болғанда болуы керек.',
    'present_unless' => ':attribute өрісі :other :value болмағанда болуы керек.',
    'present_with' => ':attribute өрісі :values болғанда болуы керек.',
    'present_with_all' => ':attribute өрісі :values болғанда болуы керек.',
    'prohibited' => ':attribute өрісі тыйым салынған.',
    'prohibited_if' => ':attribute өрісі :other :value болғанда тыйым салынған.',
    'prohibited_if_accepted' => ':attribute өрісі :other қабылданғанда тыйым салынған.',
    'prohibited_if_declined' => ':attribute өрісі :other бас тартылғанда тыйым салынған.',
    'prohibited_unless' => ':attribute өрісі :other :values ішінде болмағанда тыйым салынған.',
    'prohibits' => ':attribute өрісі :other болуын тыйым салады.',
    'regex' => ':attribute өрісінің форматы жарамсыз.',
    'required' => ':attribute өрісі міндетті.',
    'required_array_keys' => ':attribute өрісі келесілер үшін жазбаларды қамтуы керек: :values.',
    'required_if' => ':attribute өрісі :other :value болғанда міндетті.',
    'required_if_accepted' => ':attribute өрісі :other қабылданғанда міндетті.',
    'required_if_declined' => ':attribute өрісі :other бас тартылғанда міндетті.',
    'required_unless' => ':attribute өрісі :other :values ішінде болмағанда міндетті.',
    'required_with' => ':attribute өрісі :values болғанда міндетті.',
    'required_with_all' => ':attribute өрісі :values болғанда міндетті.',
    'required_without' => ':attribute өрісі :values болмағанда міндетті.',
    'required_without_all' => ':attribute өрісі :values ешқайсысы болмағанда міндетті.',
    'same' => ':attribute өрісі :other сәйкес келуі керек.',
    'size' => [
        'array' => ':attribute өрісі :size элементтен тұруы керек.',
        'file' => ':attribute өрісі :size килобайт болуы керек.',
        'numeric' => ':attribute өрісі :size болуы керек.',
        'string' => ':attribute өрісі :size таңбадан тұруы керек.',
    ],
    'starts_with' => ':attribute өрісі келесілердің біреуінен басталуы керек: :values.',
    'string' => ':attribute өрісі жол болуы керек.',
    'timezone' => ':attribute өрісі жарамды уақыт белдеуі болуы керек.',
    'unique' => ':attribute бұрын алынған.',
    'uploaded' => ':attribute жүктелу сәтсіз аяқталды.',
    'uppercase' => ':attribute өрісі бас әріппен жазылуы керек.',
    'url' => ':attribute өрісі жарамды URL болуы керек.',
    'ulid' => ':attribute өрісі жарамды ULID болуы керек.',
    'uuid' => ':attribute өрісі жарамды UUID болуы керек.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'name' => 'аты',
        'email' => 'email',
        'password' => 'құпия сөз',
        'password_confirmation' => 'құпия сөзді растау',
    ],

];
