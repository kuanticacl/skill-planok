import { AlignLeft, Code, Columns2, Heading, Image, ListOrdered, Minus, MousePointerClick, MoveVertical, PanelBottom, PanelTop, Share2 } from '@lucide/vue';
import type { Component } from 'vue';
import type { BlockType } from '@/lib/emailBuilder';

export const blockIcons: Record<BlockType, Component> = {
    header: PanelTop,
    heading: Heading,
    text: AlignLeft,
    image: Image,
    button: MousePointerClick,
    columns: Columns2,
    list: ListOrdered,
    divider: Minus,
    spacer: MoveVertical,
    social: Share2,
    footer: PanelBottom,
    html: Code,
};
