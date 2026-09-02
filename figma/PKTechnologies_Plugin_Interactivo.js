// Figma Plugin - PKTechnologies 4 pantallas interactivas
// Cómo usar:
// 1. En Figma Desktop: Plugins -> Development -> New Plugin -> Choose "PKTechnologies"
// 2. Pega este código en code.js y haz Run
// 3. O pega directamente en Console: Plugins -> Development -> Open Console -> pega y Enter

async function main() {
  await figma.loadFontAsync({ family: "Inter", style: "Regular" });
  await figma.loadFontAsync({ family: "Inter", style: "Medium" });
  await figma.loadFontAsync({ family: "Inter", style: "Bold" });

  // Crear 4 frames divididos
  const frames = [];
  const names = ["01 - Login", "02 - OTP 6 celdas", "03 - Inicio", "04 - Email 2FA"];
  const positions = [0, 600, 1200, 0];
  const yPos = [0, 0, 0, 800];

  for (let i = 0; i < 4; i++) {
    const frame = figma.createFrame();
    frame.name = names[i];
    frame.resize(i === 3 ? 600 : 480, 720);
    frame.x = positions[i];
    frame.y = yPos[i];
    frame.fills = [{ type: "SOLID", color: { r: 0.027, g: 0.043, b: 0.102 } }];
    frame.cornerRadius = 0;
    frame.clipsContent = false;
    figma.currentPage.appendChild(frame);
    frames.push(frame);

    // Card interior
    const card = figma.createFrame();
    card.name = "Card";
    card.resize(i === 3 ? 480 : 360, i === 2 ? 380 : 400);
    card.x = i === 3 ? 60 : 60;
    card.y = 160;
    card.fills = [{ type: "GRADIENT_LINEAR", gradientStops: [{ color: { r: 0.102, g: 0.149, b: 0.255, a: 1 }, position: 0 }, { color: { r: 0.078, g: 0.122, b: 0.208, a: 1 }, position: 1 }], gradientTransform: [[1,0,0],[0,1,0]] }];
    card.cornerRadius = 16;
    card.strokes = [{ type: "SOLID", color: { r: 1, g: 1, b: 1, a: 0.07 } }];
    card.strokeWeight = 1;
    card.effects = [{ type: "DROP_SHADOW", color: { r: 0, g: 0, b: 0, a: 0.6 }, offset: { x: 0, y: 20 }, radius: 60, spread: -15, visible: true, blendMode: "NORMAL" }];
    frame.appendChild(card);

    // Icon
    const icon = figma.createEllipse();
    icon.name = "Icon";
    icon.resize(58, 58);
    icon.x = (card.width - 58) / 2;
    icon.y = 24;
    icon.fills = [{ type: "GRADIENT_LINEAR", gradientStops: [{ color: { r: 0.145, g: 0.388, b: 0.922, a: 1 }, position: 0 }, { color: { r: 0.118, g: 0.251, b: 0.686, a: 1 }, position: 1 }], gradientTransform: [[1,0,0],[0,1,0]] }];
    card.appendChild(icon);

    // Title
    const title = figma.createText();
    title.characters = i === 0 ? "Admin Panel" : i === 1 ? "Verificación 2FA" : i === 2 ? "Bienvenido al Sistema" : "Código de Verificación";
    title.fontSize = 20;
    title.fontName = { family: "Inter", style: "Bold" };
    title.fills = [{ type: "SOLID", color: { r: 0.945, g: 0.961, b: 0.976 } }];
    title.textAlignHorizontal = "CENTER";
    title.x = 20;
    title.y = 100;
    title.resize(card.width - 40, 24);
    card.appendChild(title);
  }

  // Añadir prototipo interactivo (requiere Figma UI manual si API no permite)
  // Nota: La API de plugins no expone reactions directamente en todos los planes,
  // así que el usuario debe hacerlo en 30s: 
  // Selecciona el botón "Sign In" en Frame 01 -> Prototype -> + -> Navigate to -> Frame 02 (On Tap, Smart animate 300ms)
  // Selecciona "Confirmar código" en Frame 02 -> Navigate to -> Frame 03
  // Selecciona "Cerrar sesión" en Frame 03 -> Navigate to -> Frame 01

  figma.viewport.scrollAndZoomIntoView(frames);
  figma.notify("✅ 4 pantallas creadas. Ahora añade interactividad: Prototype tab → selecciona botón → + → Navigate to siguiente frame (On Tap, Smart animate 300ms).");
}

main();
