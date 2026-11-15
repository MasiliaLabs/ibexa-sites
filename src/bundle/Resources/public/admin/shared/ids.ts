/**
 * Typed access to the Ibexa Design System components (@ids-components).
 *
 * The design system ships JavaScript without typings, and its CommonJS re-exports are not visible to
 * TypeScript. The components used here are declared once, in this file, so the rest of the widget stays typed.
 */

import type { ComponentType, ReactNode } from 'react';
import * as ids from '@ids-components';

export type ButtonType = 'primary' | 'secondary' | 'tertiary' | 'secondary-alt' | 'tertiary-alt';

export interface ButtonProps {
    type?: ButtonType;
    size?: 'medium' | 'small';
    icon?: string;
    ariaLabel?: string;
    title?: string;
    disabled?: boolean;
    onClick?: () => void;
    extraAria?: Record<string, string | boolean>;
    children?: ReactNode;
}

export interface AssetsValue {
    getIconPath: (name: string) => string;
}

export interface DropdownSingleProps {
    name?: string;
    items: Array<{ id: string; label: string }>;
    /** The id selected; empty: nothing selected (the placeholder shows) */
    value: string;
    onChange: (id: string) => void;
    disabled?: boolean;
    error?: boolean;
    className?: string;
    /** More items than this and the list gets a search field (the design system's own rule; 10 by default) */
    maxVisibleItems?: number;
    renderEmptySelectionInfo?: () => ReactNode;
}

export type TagType = 'primary' | 'primary-alt' | 'success' | 'info' | 'warning' | 'error' | 'neutral';

export interface TagProps {
    type: TagType;
    size?: 'medium' | 'small';
    isDark?: boolean;
    icon?: string;
    children?: ReactNode;
}

export interface ChipProps {
    isDeletable?: boolean;
    onDelete?: () => void;
    disabled?: boolean;
    error?: boolean;
    children?: ReactNode;
}

export interface BadgeProps {
    variant?: 'string' | 'number';
    size?: 'medium' | 'small';
    value: string | number;
    maxValue?: number;
}

export interface AccordionProps {
    header: ReactNode;
    /** Follows the prop: when it changes the accordion opens or closes with it */
    initiallyExpanded?: boolean;
    onHandleExpand?: (expanded: boolean) => void;
    children?: ReactNode;
}

export interface TranslatorValue {
    trans: (id: string) => string;
}

interface IdsComponents {
    Button: ComponentType<ButtonProps>;
    AssetsProvider: ComponentType<{ value: AssetsValue; children?: ReactNode }>;
    TranslatorProvider: ComponentType<{ value: TranslatorValue; children?: ReactNode }>;
    DropdownSingleInput: ComponentType<DropdownSingleProps>;
    Tag: ComponentType<TagProps>;
    Chip: ComponentType<ChipProps>;
    Badge: ComponentType<BadgeProps>;
    Accordion: ComponentType<AccordionProps>;
}

const components = ids as unknown as IdsComponents;

export const Button = components.Button;
export const AssetsProvider = components.AssetsProvider;
export const TranslatorProvider = components.TranslatorProvider;
export const DropdownSingleInput = components.DropdownSingleInput;
export const Tag = components.Tag;
export const Chip = components.Chip;
export const Badge = components.Badge;
export const Accordion = components.Accordion;
