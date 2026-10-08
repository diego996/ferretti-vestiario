<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Operazione rifiutata per lo stato attuale dei dati (es. consegna già annullata,
 * collega disattivato). Il messaggio è pensato per essere mostrato all'operatore.
 */
class OperazioneNonConsentitaException extends RuntimeException {}
