<?php

/*
 * Messaggi di validazione in italiano per le regole usate dal gestionale.
 * Le regole non elencate ricadono sul messaggio generico "invalid".
 */
return [
    'accepted' => 'Il campo :attribute deve essere accettato.',
    'after' => 'Il campo :attribute deve essere una data successiva al :date.',
    'after_or_equal' => 'Il campo :attribute deve essere una data uguale o successiva al :date.',
    'array' => 'Il campo :attribute deve essere un elenco.',
    'before' => 'Il campo :attribute deve essere una data precedente al :date.',
    'before_or_equal' => 'Il campo :attribute deve essere una data uguale o precedente al :date.',
    'between' => [
        'numeric' => 'Il campo :attribute deve essere compreso tra :min e :max.',
        'string' => 'Il campo :attribute deve contenere tra :min e :max caratteri.',
        'array' => 'Il campo :attribute deve contenere tra :min e :max elementi.',
    ],
    'boolean' => 'Il campo :attribute deve essere vero o falso.',
    'confirmed' => 'La conferma di :attribute non corrisponde.',
    'date' => 'Il campo :attribute non è una data valida.',
    'date_format' => 'Il campo :attribute non rispetta il formato :format.',
    'different' => 'I campi :attribute e :other devono essere diversi.',
    'distinct' => 'Il campo :attribute contiene un valore duplicato.',
    'email' => 'Il campo :attribute deve essere un indirizzo email valido.',
    'exists' => 'Il valore selezionato per :attribute non è valido.',
    'gt' => [
        'numeric' => 'Il campo :attribute deve essere maggiore di :value.',
    ],
    'gte' => [
        'numeric' => 'Il campo :attribute deve essere maggiore o uguale a :value.',
    ],
    'in' => 'Il valore selezionato per :attribute non è valido.',
    'integer' => 'Il campo :attribute deve essere un numero intero.',
    'invalid' => 'Il valore di :attribute non è valido.',
    'lt' => [
        'numeric' => 'Il campo :attribute deve essere minore di :value.',
    ],
    'lte' => [
        'numeric' => 'Il campo :attribute deve essere minore o uguale a :value.',
    ],
    'max' => [
        'numeric' => 'Il campo :attribute non può essere superiore a :max.',
        'string' => 'Il campo :attribute non può superare :max caratteri.',
        'array' => 'Il campo :attribute non può avere più di :max elementi.',
        'file' => 'Il file :attribute non può superare :max kilobyte.',
    ],
    'min' => [
        'numeric' => 'Il campo :attribute deve essere almeno :min.',
        'string' => 'Il campo :attribute deve contenere almeno :min caratteri.',
        'array' => 'Il campo :attribute deve avere almeno :min elementi.',
    ],
    'not_in' => 'Il valore selezionato per :attribute non è valido.',
    'numeric' => 'Il campo :attribute deve essere un numero.',
    'password' => [
        'letters' => 'La :attribute deve contenere almeno una lettera.',
        'mixed' => 'La :attribute deve contenere almeno una maiuscola e una minuscola.',
        'numbers' => 'La :attribute deve contenere almeno un numero.',
        'symbols' => 'La :attribute deve contenere almeno un simbolo.',
        'uncompromised' => 'La :attribute indicata compare in una violazione di dati: scegline un\'altra.',
    ],
    'present' => 'Il campo :attribute deve essere presente.',
    'prohibited' => 'Il campo :attribute non è ammesso.',
    'required' => 'Il campo :attribute è obbligatorio.',
    'required_if' => 'Il campo :attribute è obbligatorio quando :other è :value.',
    'required_with' => 'Il campo :attribute è obbligatorio quando è presente :values.',
    'same' => 'I campi :attribute e :other devono coincidere.',
    'size' => [
        'numeric' => 'Il campo :attribute deve essere :size.',
        'string' => 'Il campo :attribute deve contenere :size caratteri.',
    ],
    'string' => 'Il campo :attribute deve essere un testo.',
    'unique' => 'Il valore di :attribute è già utilizzato.',
    'uuid' => 'Il campo :attribute deve essere un UUID valido.',

    'custom' => [],

    'attributes' => [
        'email' => 'email',
        'password' => 'password',
        'name' => 'nome',
        'nome' => 'nome',
        'cognome' => 'cognome',
        'matricola' => 'matricola',
        'codice' => 'codice',
        'descrizione' => 'descrizione',
        'categoria_id' => 'categoria',
        'collega_id' => 'collega',
        'commessa_id' => 'commessa',
        'variante_id' => 'taglia',
        'taglia' => 'taglia',
        'quantita' => 'quantità',
        'scorta_minima' => 'scorta minima',
        'note' => 'note',
        'motivo_annullamento' => 'motivazione',
    ],
];
