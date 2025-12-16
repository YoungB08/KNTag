
    const FONT_MAP = {
      "inter": "Inter",
      "poppins": "Poppins",
      "dm-sans": "DM Sans",
      "manrope": "Manrope",
      "plus-jakarta-sans": "Plus Jakarta Sans",
      "outfit": "Outfit",
      "space-grotesk": "Space Grotesk",
      "rubik": "Rubik",
      "urbanist": "Urbanist",
      "mulish": "Mulish",
      "work-sans": "Work Sans",
      "open-sans": "Open Sans",
      "source-sans-3": "Source Sans 3",
      "ibm-plex-sans": "IBM Plex Sans",
      "figtree": "Figtree",
      "sora": "Sora",
      "lexend": "Lexend",
      "lato": "Lato",
      "nunito": "Nunito",
      "quicksand": "Quicksand",
      "varela-round": "Varela Round",
      "montserrat": "Montserrat",
      "raleway": "Raleway",
      "josefin-sans": "Josefin Sans",
      "playfair-display": "Playfair Display",
      "cormorant-garamond": "Cormorant Garamond",
      "merriweather": "Merriweather",
      "eb-garamond": "EB Garamond",
      "fraunces": "Fraunces",
      "prata": "Prata",
      "rajdhani": "Rajdhani",
      "orbitron": "Orbitron",
      "oxanium": "Oxanium",
      "chakra-petch": "Chakra Petch",
      "comfortaa": "Comfortaa",
      "fredoka": "Fredoka",
      "pacifico": "Pacifico",
      "caveat": "Caveat",
      "be-vietnam-pro": "Be Vietnam Pro"
    };


    (function() {
      const select = document.getElementById("font-style-select");
      if (!select) return;

      // ====== PREVIEW TARGET (bắt đúng card preview) ======
      const previewTarget =
        document.querySelector(".bio-shell") ||
        document.querySelector(".preview-bio") ||
        document.querySelector(".preview-card") ||
        document.querySelector(".bio-avatar")?.closest("section") ||
        document.querySelector("main");

      // ====== FONT LIST ======
      const FONTS = [{
          key: "inter",
          family: "Inter",
          demo: "Bio & NFC Card Designer"
        },
        {
          key: "poppins",
          family: "Poppins",
          demo: "Link in bio"
        },
        {
          key: "dm-sans",
          family: "DM Sans",
          demo: "Personal Branding"
        },
        {
          key: "manrope",
          family: "Manrope",
          demo: "Creator portfolio"
        },
        {
          key: "outfit",
          family: "Outfit",
          demo: "Modern bio page"
        },
        {
          key: "space-grotesk",
          family: "Space Grotesk",
          demo: "Tech & minimal"
        },
        {
          key: "rubik",
          family: "Rubik",
          demo: "Clean & friendly"
        },
        {
          key: "urbanist",
          family: "Urbanist",
          demo: "Sleek profile"
        },
        {
          key: "mulish",
          family: "Mulish",
          demo: "Professional look"
        },
        {
          key: "work-sans",
          family: "Work Sans",
          demo: "Simple. Sharp."
        },
        {
          key: "montserrat",
          family: "Montserrat",
          demo: "Luxury bio"
        },
        {
          key: "raleway",
          family: "Raleway",
          demo: "Elegant links"
        },
        {
          key: "josefin-sans",
          family: "Josefin Sans",
          demo: "Premium vibe"
        },
        {
          key: "playfair-display",
          family: "Playfair Display",
          demo: "Signature style"
        },
        {
          key: "be-vietnam-pro",
          family: "Be Vietnam Pro",
          demo: "Bio tiếng Việt"
        }
      ];

      // ====== 1️⃣ injectGoogleFonts (HÀM M BỊ THIẾU) ======
      function injectGoogleFonts(families) {
        const chunkSize = 20;
        for (let i = 0; i < families.length; i += chunkSize) {
          const chunk = families.slice(i, i + chunkSize)
            .map(f => `family=${encodeURIComponent(f)}:wght@400;500;600;700`)
            .join("&");
          const link = document.createElement("link");
          link.rel = "stylesheet";
          link.href = `https://fonts.googleapis.com/css2?${chunk}&display=swap`;
          document.head.appendChild(link);
        }
      }

      // ====== 2️⃣ build options ======
      function buildOptions() {
        select.innerHTML = "";
        FONTS.forEach(f => {
          const opt = document.createElement("option");
          opt.value = f.key;
          opt.textContent = `${f.family} — ${f.demo}`;
          opt.style.fontFamily = `"${f.family}", Inter, system-ui, sans-serif`;
          select.appendChild(opt);
        });
      }

      function applyFontKey(fontKey) {
        const f = FONTS.find(x => x.key === fontKey) || FONTS[0];
        if (!f) return;

        // load font nếu chưa có
        const id = "gf-" + f.family.replace(/\s+/g, "-").toLowerCase();
        if (!document.getElementById(id)) {
          const link = document.createElement("link");
          link.id = id;
          link.rel = "stylesheet";
          link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(f.family)}:wght@400;500;600;700&display=swap`;
          document.head.appendChild(link);
        }

        // 🔥 ÉP FONT CHO TOÀN BỘ PREVIEW (KỂ CẢ CON)
        const preview =
          document.querySelector(".bio-shell") ||
          document.querySelector(".preview-bio") ||
          document.querySelector(".preview-card") ||
          document.querySelector("[class*='preview']") ||
          document.querySelector("main");

        if (!preview) return;

        preview.style.setProperty(
          "font-family",
          `"${f.family}", Inter, system-ui, sans-serif`,
          "important"
        );

        // 🔥 ÉP LUÔN CHO TẤT CẢ CON (đè CSS cũ)
        preview.querySelectorAll("*").forEach(el => {
          el.style.setProperty(
            "font-family",
            `"${f.family}", Inter, system-ui, sans-serif`,
            "important"
          );
        });

        localStorage.setItem("bio_font_style", fontKey);
      }

      // ====== INIT FLOW (ĐÂY LÀ “INIT VÀO SELECT”) ======
      injectGoogleFonts(FONTS.map(f => f.family)); // ⬅️ HẾT LỖI Ở ĐÂY
      buildOptions();

      const saved = localStorage.getItem("bio_font_style");
      if (saved && FONTS.some(f => f.key === saved)) {
        select.value = saved;
      }

      applyFontKey(select.value);

      select.addEventListener("change", () => {
        applyFontKey(select.value);
      });
    })();
  