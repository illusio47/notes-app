-- Migration: Add voice note support to notes table
-- Run this after initial database setup

USE notes_app;

-- Add columns for voice note support
ALTER TABLE notes 
ADD COLUMN audio_file VARCHAR(255) NULL AFTER content,
ADD COLUMN is_voice_note TINYINT(1) DEFAULT 0 AFTER audio_file;

-- Add index for voice notes
CREATE INDEX idx_is_voice_note ON notes (is_voice_note);
