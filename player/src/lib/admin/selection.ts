export function selectionState(visible: string[], selected: string[]) {
    const count = visible.filter((id) => selected.includes(id)).length;
    return { all: visible.length > 0 && count === visible.length, some: count > 0 && count < visible.length };
}
export function toggleVisible(visible: string[], selected: string[]) {
    return selectionState(visible, selected).all
        ? selected.filter((id) => !visible.includes(id))
        : [...new Set([...selected, ...visible])];
}
