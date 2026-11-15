/**
 * Typed access to the Ibexa Design System components (@ids-components).
 *
 * The design system ships JavaScript without typings, and its CommonJS re-exports are not visible to
 * TypeScript. The components used here are declared once, in this file, so the rest of the widget stays typed.
 */

import type { ComponentType, ReactNode } from 'react';
import * as ids from '@ids-components';
// The package root re-exports AltRadioInput but not the list field: it is reached by its own path.
import * as altRadioList from '@ids-components/components/AltRadio/AltRadiosListField';

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

export interface IconProps {
    name: string;
    size?: 'small' | 'medium' | 'large';
    className?: string;
}

export interface AltRadioItem {
    id: string;
    value: string;
    label: string;
    title?: string;
}

export interface AltRadiosListFieldProps {
    id: string;
    name: string;
    label?: string;
    items: AltRadioItem[];
    value: string;
    onChange: (value: string) => void;
    direction?: 'horizontal' | 'vertical';
    helperText?: string;
}

interface IdsComponents {
    Button: ComponentType<ButtonProps>;
    Icon: ComponentType<IconProps>;
    AssetsProvider: ComponentType<{ value: AssetsValue; children?: ReactNode }>;
}

const components = ids as unknown as IdsComponents;

export const AltRadiosListField = (altRadioList as unknown as { AltRadiosListField: ComponentType<AltRadiosListFieldProps> }).AltRadiosListField;
export const Button = components.Button;
export const Icon = components.Icon;
export const AssetsProvider = components.AssetsProvider;
