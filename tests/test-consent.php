<?php
declare(strict_types=1);
// Predicate demonstration mirrors active_consent query semantics.
function consent_is_active(?array $row): bool { return $row!==null && $row['withdrawn_at']===null; }
if (consent_is_active(null) || consent_is_active(['withdrawn_at'=>'2026-01-01 00:00:00']) || !consent_is_active(['withdrawn_at'=>null])) throw new RuntimeException('FAIL: consent state');
echo "PASS: missing/withdrawn consent rejected, active consent allowed\n";
