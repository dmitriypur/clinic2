# Article Reading Time

## Goal

Replace the hard-coded article reading time with a value calculated from the
article's main text.

## Data Source

Only blocks with type `BlockType::POST_TEXT` belonging to the page are included.
FAQ, author, forms, navigation, captions, and other supporting blocks are
excluded.

## Calculation

1. Join the `body_html` values of the page's `POST_TEXT` blocks.
2. Convert HTML entities and remove HTML tags.
3. Count Unicode words.
4. Divide the word count by 200 words per minute.
5. Round up and return at least one minute.

## Placement

The calculation belongs to the `Page` model as a reusable computed attribute.
The author Blade component reads the attribute and formats the Russian minute
label.

## Verification

Add focused model tests covering multiple text blocks, excluded block types,
HTML markup, rounding, and an empty article.
