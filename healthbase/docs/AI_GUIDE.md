# AI GUIDE

## Модель
ID модели хранится в `.env` (`GEMINI_MODEL_ID`) и в конфиге по умолчанию.

## Контур точности
1. Rule preclassification (словари).
2. Gemini classification (`classification_v1`).
3. Gemini validation (`validation_v1`).
4. confidence + verdict.
5. Auto-approve только при `confidence >= threshold` и `verdict=auto_approve`.
6. Иначе manual review.
7. При `manual_lock=1` auto-изменения запрещены.

## Prompt versioning
Промпты в `app/AI/Prompts/*_v1.txt`.
