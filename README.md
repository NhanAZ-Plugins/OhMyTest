# OhMyTest

OhMyTest is an experimental PocketMine-MP plugin for inspecting worlds, items, permissions and glyphs. Its test features are disabled by default because some of them load worlds, generate terrain or change player state.

## Requirements and compatibility

- `plugin.yml` declares PocketMine-MP API `5.0.0`.
- PHPStan at maximum level, isolated server boot and offline headless Bedrock client sessions have been checked against the Axolotl-PM revision pinned in the [build workflow](.github/workflows/build.yml). This does not establish compatibility with every 5.x release or with the Bedrock game UI.
- Use a disposable server and world for experimental testing. Do not enable the features on a production world.

## Configuration and commands

The default [`config.yml`](resources/config.yml) contains `enable-experimental-features: false`. With that value, the plugin loads but does not register its test listeners, load additional worlds, generate spawn terrain or write documentation files.

Set the value to `true` only in an isolated test server. Startup then writes `Glyph.md` and `permissions.md` to the plugin data directory. Players with `ohmytest.experimental`, which defaults to operators, can use these chat aliases:

| Alias | Test action |
|---|---|
| `tp <world>` | Move to the safe spawn of a loaded world. |
| `nbt` | Read the held item's `blockdata` string. |
| `setnbt <key> <value>` | Set a string in the held item's custom block data. |
| `egd` | Add a test item to the inventory. |
| `vd` | Show the held item's name, count and type ID. |
| `ws` | List loaded worlds. |
| `fly` | Allow flight for the player. |
| `pause`, `resume` | Broadcast a pause state packet. |

Recognized aliases are removed from chat. Players without the permission receive a denial message. These are chat aliases for test use, not registered slash commands.

A source fixture checks alias recognition, permission denial, missing arguments, authorized flight and held-item NBT writes and reads with a synthetic player and item. An isolated Axolotl-PM server probe covered the chat and flight paths using a synthetic Player and a real `PlayerChatEvent`. A local offline headless Bedrock client received the `fly` permission denial before being made an operator. It then received the expected item description and `client-probe` value after `egd`, `vd`, `setnbt blockdata client-probe` and `nbt`. In a later session, `ws` listed `world`, `tp world` returned `Moved to world`, `fly` enabled the `may_fly` and `flying` flags in ability packets, and `pause` and `resume` sent pause packets with states 1 and 0.

A three-boot check used the same verified PHAR and player account. The player received the test item, set its `blockdata` string, disconnected, and received the same item name, count and value after each restart. The test server saved a player data file. In the second boot, an empty disposable world generated its spawn terrain. The third boot loaded that world without generating spawn terrain again. A separate two-boot check used `tp other` to move the connected player from the default world into that disposable world. After restart, the player logged in directly to `other` at the saved position. These checks cover network chat, held-item NBT, player inventory persistence, generated spawn terrain and cross-world teleport persistence in an isolated server. They do not verify the Bedrock game UI, every experimental action or persistence of edits to world blocks.

## Build and support

The [build workflow](.github/workflows/build.yml) runs PHPStan at maximum level, lints PHP, checks the chat alias fixture and builds a PHAR with pinned [DevTools](https://github.com/NhanAZ/DevTools). It verifies the manifest, config, source and digest, then uploads the checked PHAR as a temporary CI artifact. This is not a stable release. The root [LICENSE](LICENSE) contains the project's GPL-3.0 terms.

Report issues through [GitHub Issues](https://github.com/NhanAZ-Plugins/OhMyTest/issues) or [NhanAZ Discord](https://discord.gg/j2X83ujT6c).
