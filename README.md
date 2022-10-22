# @iammati/grids2container

TYPO3 extension to migrate from GridElements to b13/container.

This extension covers a migration of:
- TSconfig from GridElements columns to generated TCA with exact same colPos
- FlexForm to TCA for container usage - since it only requires TCA
- Dynamic creation of TypoScript to render and point to your templates

Left-overs you would need to take care of:
- Updating the passed records-variables of a container's child-records. That's it!

## Note

This extension is not capable of migrating GridElement's container-inside-container since such an implementation is missing yet and I'm not finding the time to add this (nor have a project yet for that). Feel free to perform a PR for this. :)

## Why

Since GridElements is running late, like really, really late, I wanted to make it easier during upgrades to migrate
to a way more simple container-extension. The reasons why you should do that are [listed here](https://github.com/b13/container#why-did-we-create-another-grid-extension).

Personal opinion: it's kinda shitty from GridElements that they aren't even able to fulfill the timings of LTS release cycle, which is the goal of b13/container. ;/
