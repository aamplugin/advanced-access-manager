#!/usr/bin/env python3
"""Fill missing PO entries with local Argos Translate drafts.

Install argostranslate and polib in a separate environment, then provide an
English-to-target Argos model. Existing translations are never changed.
"""

import argparse
import re
from collections import Counter

import polib

import argostranslate.networking as networking

# UI labels are short; translate each as a single unit without downloading an
# additional sentence-boundary model.
networking.cache_spacy = lambda: None
import argostranslate.translate as translate  # noqa: E402


class WholeStringSentencizer:
    def __init__(self, package):
        pass

    def split_sentences(self, text):
        return [text]


translate.StanzaSentencizer = WholeStringSentencizer
translate.SpacySentencizerSmall = WholeStringSentencizer

PROTECTED = re.compile(
    r"%(?:\d+\$)?[-+#0 ]*(?:\d+|\*)?(?:\.(?:\d+|\*))?[bcdeEfFgGosuxX%]"
    r"|<[^>]+>|https?://[^\s<>]+|\$\{[^}]+\}"
)
TARGETS = {
    "de_DE": "de", "es_ES": "es", "fr_FR": "fr", "it_IT": "it",
    "ja": "ja", "nl_NL": "nl", "pl_PL": "pl", "pt_BR": "pt",
    "ru_RU": "ru", "zh_CN": "zh",
}


def draft(text, translator):
    tokens = {}

    def protect(match):
        token = f"ZXQ{len(tokens)}QXZ"
        tokens[token] = match.group()
        return token

    masked = PROTECTED.sub(protect, text)
    result = translator.translate(masked).strip()
    if all(result.count(token) == 1 for token in tokens):
        for token, original in tokens.items():
            result = result.replace(token, original)
        if Counter(PROTECTED.findall(result)) == Counter(PROTECTED.findall(text)):
            return result or None

    # Some models drop or alter token markers. Translate the text between
    # placeholders separately so the draft keeps every formatting token.
    parts = PROTECTED.split(text)
    markers = PROTECTED.findall(text)
    translated = []
    for index, part in enumerate(parts):
        if re.search(r"[A-Za-z]", part):
            leading = re.match(r"^\s*", part).group()
            trailing = re.search(r"\s*$", part).group()
            body = part.strip()
            part = leading + translator.translate(body).strip() + trailing
        translated.append(part)
        if index < len(markers):
            translated.append(markers[index])
    result = "".join(translated)
    if Counter(PROTECTED.findall(result)) != Counter(markers):
        return None
    return result or None


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("locale", choices=TARGETS)
    parser.add_argument("--limit", type=int, default=0)
    args = parser.parse_args()
    path = f"lang/advanced-access-manager-{args.locale}.po"
    catalog = polib.pofile(path)
    translator = translate.get_translation_from_codes("en", TARGETS[args.locale])
    if translator is None:
        raise RuntimeError(f"English-to-{TARGETS[args.locale]} model is missing")

    drafted = 0
    skipped = 0
    for entry in catalog:
        if entry.obsolete or entry.msgstr or any(entry.msgstr_plural.values()):
            continue
        if args.limit and drafted >= args.limit:
            break
        if entry.msgid_plural:
            singular = draft(entry.msgid, translator)
            plural = draft(entry.msgid_plural, translator)
            if singular is None or plural is None:
                skipped += 1
                continue
            for form in entry.msgstr_plural:
                entry.msgstr_plural[form] = singular if form == 0 else plural
            entry.tcomment = (entry.tcomment + "\n" if entry.tcomment else "") \
                + "Machine translation draft (Argos Translate); native review needed."
            drafted += 1
            continue
        source = entry.msgid
        if not re.search(r"[A-Za-z]", source):
            candidate = source
        elif source.startswith("http://") or source.startswith("https://"):
            candidate = source
        else:
            candidate = draft(source, translator)
        if candidate is None:
            skipped += 1
            continue
        entry.msgstr = candidate
        entry.tcomment = (entry.tcomment + "\n" if entry.tcomment else "") \
            + "Machine translation draft (Argos Translate); native review needed."
        drafted += 1
        if drafted % 100 == 0:
            print(f"{args.locale}: drafted {drafted}", flush=True)

    catalog.save(path)
    print(f"{args.locale}: {drafted} drafted, {skipped} skipped", flush=True)


if __name__ == "__main__":
    main()
