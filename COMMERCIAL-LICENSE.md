# 商业许可 / Commercial License

**中文在前，English below.**

## 这个项目是开源的，商业使用是允许的

本项目以 **GNU AGPL-3.0** 发布，许可证全文见 [LICENSE](LICENSE)。AGPL-3.0 是 OSI 认可的开源许可证，它**允许任何人出于任何目的使用本软件，包括商业用途**，不需要征得作者同意，也不需要付费。

你可以自由地：

- 用它做生意、对外提供服务、向你的客户收费；
- 修改它、二次开发、集成进你自己的系统；
- 分发它，无论收费与否。

## 那什么时候需要买商业许可？

AGPL-3.0 对上述自由附带**一个**条件，写在许可证第 13 条：

> 如果你修改了本软件，并且让用户通过网络与之交互（例如做成 SaaS、在线服务、对外开放的平台），你必须向这些用户提供你修改后的**完整源代码**。

对大多数使用者，这个条件毫无负担 —— 自建自用、内部部署、不改代码直接用，都不触发它。

但如果你的情况是：

- 想把本项目改造后作为**闭源**的商业产品或 SaaS 对外提供，不愿公开自己的修改；
- 想把本项目集成进你自己的**专有软件**，而该软件不能以 AGPL 发布；
- 你的客户或合规部门不接受 AGPL 的传染性条款。

那么**商业许可就是为你准备的**。它是一份**可选的替代许可**：购买后，你依据商业许可条款使用本项目，从而不再受 AGPL-3.0 第 13 条的源码公开义务约束。

**请注意措辞：商业许可不是对 AGPL 用户的额外限制，而是一条可选的替代路径。** 不买商业许可的人，依然完整享有 AGPL-3.0 赋予的全部权利，包括商用。这是 MySQL、Qt、MongoDB、Grafana 等项目长期采用的标准双授权模式。

## 商业许可包含什么

| 项目 | AGPL-3.0（免费） | 商业许可（付费） |
| --- | --- | --- |
| 商业使用 | ✅ 允许 | ✅ 允许 |
| 修改、二次开发 | ✅ 允许 | ✅ 允许 |
| 分发、转售 | ✅ 允许 | ✅ 允许 |
| 网络服务须公开修改源码（§13） | ⚠️ **必须** | ✅ **豁免** |
| 集成进闭源专有软件 | ❌ 不可 | ✅ 可以 |
| 保留版权声明与许可声明 | 必须 | 必须 |

## 如何洽谈

请在本仓库提交一个 Issue 说明用途，我们会在 Issue 中沟通：

👉 **https://github.com/jasonpan168/ajie-shop/issues**

请不要发送邮件 —— GitHub Issue 可以留存记录、方便附材料，也不会漏进垃圾邮件箱。

## 第三方组件

商业许可仅覆盖本项目的自有代码。仓库中内置的第三方组件由其各自作者授权，其许可证不受本商业许可影响，商业许可的持有者同样需要遵守这些组件各自的许可条款。具体清单见 README 的第三方组件章节与各组件目录下的 LICENSE 文件。

---

# Commercial License (English)

## This project is open source, and commercial use is allowed

This project is released under the **GNU AGPL-3.0**; the full text is in [LICENSE](LICENSE).
AGPL-3.0 is an OSI-approved open-source licence, and it **permits anyone to use this
software for any purpose, including commercial purposes**, with no permission and no
payment required.

You are free to run a business on it, offer it as a service, charge your own customers,
modify it, build on it, integrate it, and redistribute it.

## So when would you need a commercial licence?

AGPL-3.0 attaches **one** condition to those freedoms, in section 13:

> If you modify this software and let users interact with it over a network (a SaaS, an
> online service, a public platform), you must offer those users the **complete
> corresponding source code** of your modified version.

For most users that condition costs nothing — self-hosting, internal deployment, or
using it unmodified never triggers it.

The commercial licence exists for the case where you want to ship a **closed-source**
product or SaaS built on this project, or integrate it into **proprietary software**
that cannot be released under the AGPL, or where your customers or compliance team will
not accept a copyleft licence.

It is an **optional alternative licence**: once purchased, you use the project under the
commercial terms instead, and the section 13 source-disclosure obligation no longer
applies to you.

**Note the framing: the commercial licence is not an extra restriction on AGPL users.**
It is an alternative path. Anyone who does not buy it still holds every right AGPL-3.0
grants, commercial use included. This is the standard dual-licensing model used by
MySQL, Qt, MongoDB and Grafana.

## What it covers

| | AGPL-3.0 (free) | Commercial (paid) |
| --- | --- | --- |
| Commercial use | ✅ allowed | ✅ allowed |
| Modification | ✅ allowed | ✅ allowed |
| Redistribution, resale | ✅ allowed | ✅ allowed |
| Network use requires publishing your source (§13) | ⚠️ **required** | ✅ **waived** |
| Linking into closed-source proprietary software | ❌ no | ✅ yes |
| Keep copyright and licence notices | required | required |

## Getting in touch

Please open an issue in this repository describing your use case:

👉 **https://github.com/jasonpan168/ajie-shop/issues**

Please do not email — an issue keeps the discussion and the record in one place, and
nothing gets lost in a spam folder.

## Third-party components

The commercial licence covers this project's own code only. Bundled third-party
components remain under their own licences, which the commercial licence does not alter;
commercial licensees must still comply with them. See the third-party section of the
README and the LICENSE file inside each component directory.
